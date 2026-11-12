<?php

namespace App\Services\Stats;

use App\Models\Order;
use App\Models\Ticket;
use App\Support\Stats\DatePeriod;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Chiffres des écrans PROMOTEUR (L4). Tout est restreint à Scope::promoter($id) : un promoteur ne voit jamais
 * les ventes, billets ou commissions d'un autre. Compose les services Stats (mêmes définitions que l'admin) et
 * ajoute ce qui est propre au promoteur : événements, croissance, listes et tableaux par événement.
 * Lecture seule ; agrégats SQL : le nombre de requêtes ne dépend pas du nombre de commandes ni d'événements.
 */
class PromoterStats
{
    public function __construct(
        private readonly DashboardStats $dashboard,
        private readonly SalesStats $sales,
        private readonly TicketStats $tickets,
        private readonly CommissionStats $commissions,
        private readonly EventStats $events,
    ) {}

    /**
     * Tableau de bord : DashboardStats::summary du promoteur + événements, réservés, remplissage, croissance.
     * « Net à recevoir » (pending_payout) est un SOLDE toutes dates ; le reste suit la période.
     */
    public function overview(int $promoterId, DatePeriod $period): array
    {
        $scope = Scope::promoter($promoterId);

        return $this->dashboard->summary($scope, $period) + [
            'events' => $this->eventCounts($promoterId, $period),
            'tickets_reserved' => $this->tickets->reserved($scope),
            'capacity' => $this->tickets->capacity($scope),
            'tickets_available' => $this->tickets->available($scope),
            'fill_rate' => $this->tickets->fillRate($scope),
            'growth' => $this->growth($scope, $period),
        ];
    }

    /**
     * Croissance des ventes (GMV) par rapport à la période précédente de MÊME durée.
     * `rate` est null quand elle n'est pas calculable (période « toute la période » ou ventes précédentes nulles).
     *
     * @return array{rate: ?float, previous_gmv: ?int, previous_label: ?string}
     */
    public function growth(Scope $scope, DatePeriod $period): array
    {
        $previous = $period->previous();
        if ($previous === null) {
            return ['rate' => null, 'previous_gmv' => null, 'previous_label' => null];
        }

        $before = $this->sales->gmv($scope, $previous);
        $now = $this->sales->gmv($scope, $period);

        return [
            'rate' => $before > 0 ? round(($now - $before) / $before * 100, 1) : null,
            'previous_gmv' => $before,
            'previous_label' => $previous->label,
        ];
    }

    /**
     * Événements du promoteur : total et publiés (instantané), créés sur la période. Une requête.
     *
     * @return array{total: int, published: int, draft: int, created: int}
     */
    public function eventCounts(int $promoterId, DatePeriod $period): array
    {
        $in = '1 = 1';
        $bindings = [];
        if ($period->start !== null) {
            $in .= ' AND events.created_at >= ?';
            $bindings[] = $period->startForDb();
        }
        if ($period->end !== null) {
            $in .= ' AND events.created_at <= ?';
            $bindings[] = $period->endForDb();
        }

        $row = DB::table('events')->where('events.promoter_id', $promoterId)
            ->selectRaw("COUNT(*) as total,
                SUM(CASE WHEN events.status = 'published' THEN 1 ELSE 0 END) as published,
                SUM(CASE WHEN events.status = 'draft' THEN 1 ELSE 0 END) as draft,
                SUM(CASE WHEN {$in} THEN 1 ELSE 0 END) as created", $bindings)
            ->first();

        return ['total' => (int) $row->total, 'published' => (int) $row->published, 'draft' => (int) $row->draft, 'created' => (int) $row->created];
    }

    /** Dernières commandes PAYÉES de la période (liste courte, relations chargées d'avance). */
    public function recentPaidOrders(int $promoterId, DatePeriod $period, int $limit = 10): Collection
    {
        return $this->paidOrdersQuery($promoterId, $period)->limit($limit)->get();
    }

    /** Commandes payées de la période, paginées (même requête que les cartes : mêmes commandes). */
    public function paidOrdersPage(int $promoterId, DatePeriod $period, int $perPage = 20): LengthAwarePaginator
    {
        return $this->paidOrdersQuery($promoterId, $period)->paginate($perPage)->withQueryString();
    }

    /**
     * Ventes par jour (jusqu'à 62 jours) ou par mois au-delà : jours sans vente omis pour les tableaux.
     *
     * @return array{unit: string, rows: list<array{date: string, orders: int, gmv: int, collected: int}>}
     */
    public function salesSeries(int $promoterId, DatePeriod $period, bool $skipEmpty = true): array
    {
        $scope = Scope::promoter($promoterId);
        $span = $period->isBounded() ? (int) round($period->start->diffInDays($period->end)) + 1 : PHP_INT_MAX;

        $unit = $span <= 62 ? 'day' : 'month';
        $rows = $unit === 'day' ? $this->sales->byDay($scope, $period) : $this->sales->byMonth($scope, $period);

        if ($skipEmpty) {
            $rows = array_values(array_filter($rows, fn ($r) => $r['orders'] > 0));
        }

        return ['unit' => $unit, 'rows' => $rows];
    }

    /**
     * Évolution mensuelle financière : ventes, commission et net par mois de vente. Deux requêtes (ventes + commissions).
     *
     * @return list<array{date: string, orders: int, gmv: int, commission: int, net: int}>
     */
    public function monthlyFinancials(int $promoterId, DatePeriod $period): array
    {
        $scope = Scope::promoter($promoterId);
        $commissions = $this->commissions->byMonth($scope, $period);

        return array_values(array_filter(array_map(fn ($r) => [
            'date' => $r['date'],
            'orders' => $r['orders'],
            'gmv' => $r['gmv'],
            'commission' => $commissions[$r['date']]['commission'] ?? 0,
            'net' => $commissions[$r['date']]['net'] ?? 0,
        ], $this->sales->byMonth($scope, $period)), fn ($r) => $r['orders'] > 0));
    }

    /**
     * Événements du promoteur avec leurs chiffres (EventStats::forEvents) sur la période (null : toutes dates),
     * triés par ventes décroissantes. Cinq requêtes quel que soit le nombre d'événements.
     *
     * @return list<array<string, mixed>>
     */
    public function eventsReport(int $promoterId, DatePeriod $period): array
    {
        $events = DB::table('events')->where('promoter_id', $promoterId)
            ->select('id', 'title', 'status', 'event_date')->get();

        $figures = $this->events->forEvents($events->pluck('id')->map(fn ($id) => (int) $id)->all(), $period);

        return $events->map(fn ($e) => ['id' => (int) $e->id, 'title' => $e->title, 'status' => $e->status, 'event_date' => $e->event_date] + $figures[(int) $e->id])
            ->sortByDesc('gmv')->values()->all();
    }

    /**
     * Chiffres cumulés (toutes dates) d'une page d'événements du promoteur : vendus, capacité, remplissage,
     * commandes payées, ventes, net. Cinq requêtes pour toute la page. Les identifiants viennent du contrôleur
     * Les identifiants sont FILTRÉS sur les événements du promoteur : un événement d'un autre promoteur est ignoré
     * (zéros), quel que soit l'appelant.
     *
     * @param  list<int>  $eventIds
     * @return array<int, array{sold: int, capacity: int, fill_rate: ?float, orders_paid: int, orders_total: int, gmv: int, promoter_net: int}>
     */
    public function eventsFigures(int $promoterId, array $eventIds): array
    {
        $owned = $eventIds === [] ? [] : DB::table('events')->where('promoter_id', $promoterId)
            ->whereIn('id', array_map('intval', $eventIds))->pluck('id')->map(fn ($id) => (int) $id)->all();

        return $this->events->forEvents($owned);
    }

    /**
     * Billets vendus (sold + used d'une commande payée) par type de billet d'un événement DU PROMOTEUR (sinon vide), toutes dates.
     *
     * @return array<int, int> ticket_type_id => vendus
     */
    public function soldByTicketType(int $promoterId, int $eventId): array
    {
        if (! DB::table('events')->where('id', $eventId)->where('promoter_id', $promoterId)->exists()) {
            return [];
        }

        return Ticket::query()
            ->ofOrders(Order::query()->paid()->forScope(Scope::event($eventId)))
            ->whereIn('tickets.status', ['sold', 'used'])
            ->toBase()
            ->selectRaw('tickets.ticket_type_id as ticket_type_id, COUNT(*) as total')
            ->groupBy('tickets.ticket_type_id')
            ->pluck('total', 'ticket_type_id')
            ->map(fn ($v) => (int) $v)->all();
    }

    /**
     * Utilisation des billets par événement : vendus (sold + used d'une commande payée de la période) et scannés (used).
     * Une requête pour tous les événements.
     *
     * @return array<int, array{sold: int, scanned: int, usage_rate: ?float}> event_id => chiffres
     */
    public function usageByEvent(int $promoterId, DatePeriod $period): array
    {
        $orders = Order::query()->paid()->forScope(Scope::promoter($promoterId))->inPeriod($period);

        $rows = Ticket::query()->ofOrders($orders)
            ->join('ticket_types', 'ticket_types.id', '=', 'tickets.ticket_type_id')
            ->whereIn('tickets.status', ['sold', 'used'])
            ->toBase()
            ->selectRaw("ticket_types.event_id as event_id, COUNT(*) as sold, SUM(CASE WHEN tickets.status = 'used' THEN 1 ELSE 0 END) as scanned")
            ->groupBy('ticket_types.event_id')
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $sold = (int) $r->sold;
            $scanned = (int) $r->scanned;
            $out[(int) $r->event_id] = ['sold' => $sold, 'scanned' => $scanned, 'usage_rate' => $sold > 0 ? round($scanned / $sold * 100, 1) : null];
        }

        return $out;
    }

    /**
     * Requête d'EXPORT : mêmes commandes que l'écran (payées, période, promoteur), sans tri (l'appelant pagine par
     * identifiant avec chunkByIdDesc), commission chargée d'avance. $eventId restreint à un événement du promoteur.
     */
    public function paidOrdersForExport(int $promoterId, DatePeriod $period, ?int $eventId = null)
    {
        $query = Order::query()->paid()->forScope(Scope::promoter($promoterId))->inPeriod($period)
            ->with(['user:id,name,email', 'event:id,title', 'commission'])
            ->withSum('orderItems as tickets_quantity', 'quantity');

        return $eventId === null ? $query : $query->where('orders.event_id', $eventId);
    }

    private function paidOrdersQuery(int $promoterId, DatePeriod $period)
    {
        return Order::query()->paid()->forScope(Scope::promoter($promoterId))->inPeriod($period)
            ->with(['user:id,name,email', 'event:id,title'])
            ->withSum('orderItems as tickets_quantity', 'quantity')
            ->orderByRaw(\App\Support\Stats\SqlDate::SALE_REF.' DESC')
            ->orderByDesc('orders.id');
    }
}
