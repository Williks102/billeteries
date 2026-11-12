<?php

namespace App\Services\Stats;

use App\Models\Order;
use App\Support\Stats\DatePeriod;
use App\Support\Stats\SqlDate;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Chiffres des écrans ACHETEUR (L5). Tout est restreint à Scope::buyer($id) : un acheteur ne voit JAMAIS les
 * commandes ni les billets d'un autre. Les compteurs sont CUMULÉS « toutes dates » (l'acheteur n'a pas de filtre
 * de période) et les écrans le disent. Mêmes définitions que SalesStats / TicketStats :
 *  - Commandes : commandes de l'acheteur visibles dans son historique (hors commandes invité non payées),
 *    ventilées payées / en attente (pending + processing) / remboursées / échouées ;
 *  - Billets : sold + used des commandes payées ;
 *  - Dépensé : somme des lignes (order_items.total_price) des commandes payées (0 pour une commande gratuite) ;
 *    les commandes remboursées en sont EXCLUES et comptées à part ;
 *  - Événements à venir / passés : événements DISTINCTS des commandes PAYÉES, selon la date de l'événement
 *    (event_date, au jour près, fuseau Africa/Abidjan). Un événement daté d'aujourd'hui est « à venir » (toute la
 *    journée) ; il devient « passé » le lendemain. Un événement sans date n'est ni l'un ni l'autre.
 * Lecture seule ; agrégats SQL : le nombre de requêtes ne dépend pas du nombre de commandes.
 */
class BuyerStats
{
    public function __construct(
        private readonly SalesStats $sales,
        private readonly TicketStats $tickets,
    ) {}

    /**
     * @return array{
     *   orders_total: int, orders_paid: int, orders_awaiting: int, orders_refunded: int, orders_failed: int,
     *   tickets: int, spent: int, refunded_amount: int, events_upcoming: int, events_past: int, today: string
     * }
     */
    public function overview(int $userId, ?CarbonInterface $now = null): array
    {
        $scope = Scope::buyer($userId);
        $all = DatePeriod::all();
        $today = $this->today($now);

        $byStatus = Order::query()->forScope($scope)->visibleInBuyerHistory()
            ->toBase()->selectRaw('orders.payment_status as status, COUNT(*) as total, SUM(orders.total_amount) as amount')
            ->groupBy('orders.payment_status')->get()->keyBy('status');
        $count = fn (string ...$statuses) => (int) collect($statuses)->sum(fn ($s) => $byStatus[$s]->total ?? 0);

        $events = $this->eventCounts($scope, $today);

        return [
            'orders_total' => (int) $byStatus->sum('total'),
            'orders_paid' => $count('paid'),
            'orders_awaiting' => $count('pending', 'processing'),
            'orders_refunded' => $count('refunded'),
            'orders_failed' => $count('failed'),
            'tickets' => $this->tickets->sold($scope),
            'spent' => $this->sales->gmv($scope, $all),
            'refunded_amount' => (int) ($byStatus['refunded']->amount ?? 0),
            'events_upcoming' => $events['upcoming'],
            'events_past' => $events['past'],
            'today' => $today,
        ];
    }

    /** Dernières commandes de l'historique de l'acheteur (relations chargées en une fois). */
    public function recentOrders(int $userId, int $limit = 5): Collection
    {
        return Order::query()->forScope(Scope::buyer($userId))->visibleInBuyerHistory()
            ->with(['event.category', 'orderItems.ticketType'])
            ->orderByDesc('orders.created_at')->limit($limit)->get();
    }

    /** Commandes payées dont l'événement n'est pas passé (aujourd'hui compris), les plus récentes d'abord. */
    public function upcomingOrders(int $userId, int $limit = 3, ?CarbonInterface $now = null): Collection
    {
        return Order::query()->forScope(Scope::buyer($userId))->paid()
            ->whereIn('orders.event_id', DB::table('events')->select('id')
                ->whereRaw(SqlDate::day('events.event_date').' >= ?', [$this->today($now)]))
            ->with(['event.category', 'event.ticketTypes'])
            ->orderByDesc('orders.created_at')->limit($limit)->get();
    }

    /** @return array{upcoming: int, past: int} */
    private function eventCounts(Scope $scope, string $today): array
    {
        $day = SqlDate::day('events.event_date');
        $query = DB::table('orders')->join('events', 'events.id', '=', 'orders.event_id')
            ->where('orders.payment_status', 'paid');
        $scope->constrainOrders($query);

        $row = $query->selectRaw(
            "COUNT(DISTINCT CASE WHEN {$day} >= ? THEN events.id END) as upcoming,
             COUNT(DISTINCT CASE WHEN {$day} < ? THEN events.id END) as past",
            [$today, $today]
        )->first();

        return ['upcoming' => (int) $row->upcoming, 'past' => (int) $row->past];
    }

    private function today(?CarbonInterface $now): string
    {
        return CarbonImmutable::instance($now ?? now())->setTimezone(DatePeriod::TIMEZONE)->toDateString();
    }
}
