<?php

namespace App\Services\Stats;

use App\Models\Order;
use App\Models\Ticket;
use App\Support\Stats\DatePeriod;
use Illuminate\Support\Facades\DB;

/**
 * Chiffres PAR ÉVÉNEMENT pour les listes (une page = 5 requêtes, jamais une requête par ligne).
 * Mêmes définitions que SalesStats / TicketStats / CommissionStats : vente = commande `paid`,
 * GMV = SUM(order_items.total_price), billet vendu = sold + used d'une commande payée, net = commissions
 * pending/paid. La période (facultative) s'applique à la date de vente ; capacité = instantané.
 */
class EventStats
{
    /**
     * @param  list<int>  $eventIds
     * @return array<int, array{sold: int, capacity: int, fill_rate: ?float, orders_paid: int, orders_total: int, gmv: int, promoter_net: int}>
     */
    public function forEvents(array $eventIds, ?DatePeriod $period = null): array
    {
        $out = [];
        foreach ($eventIds as $id) {
            $out[(int) $id] = ['sold' => 0, 'capacity' => 0, 'fill_rate' => null, 'orders_paid' => 0, 'orders_total' => 0, 'gmv' => 0, 'promoter_net' => 0];
        }
        if ($eventIds === []) {
            return $out;
        }

        $paid = fn () => $this->paidOrders($eventIds, $period);

        foreach (DB::table('ticket_types')->whereIn('event_id', $eventIds)->selectRaw('event_id, SUM(quantity_available) as total')->groupBy('event_id')->get() as $r) {
            $out[(int) $r->event_id]['capacity'] = (int) $r->total;
        }

        foreach ($paid()->toBase()->selectRaw('orders.event_id as event_id, COUNT(*) as total')->groupBy('orders.event_id')->get() as $r) {
            $out[(int) $r->event_id]['orders_paid'] = (int) $r->total;
        }

        $all = Order::query()->whereIn('orders.event_id', $eventIds);
        if ($period !== null) {
            $all->inPeriod($period, 'created');
        }
        foreach ($all->toBase()->selectRaw('orders.event_id as event_id, COUNT(*) as total')->groupBy('orders.event_id')->get() as $r) {
            $out[(int) $r->event_id]['orders_total'] = (int) $r->total;
        }

        foreach (DB::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.id', $paid()->select('orders.id'))
            ->selectRaw('orders.event_id as event_id, SUM(order_items.total_price) as total')->groupBy('orders.event_id')->get() as $r) {
            $out[(int) $r->event_id]['gmv'] = (int) $r->total;
        }

        foreach (DB::table('commissions')->join('orders', 'orders.id', '=', 'commissions.order_id')
            ->whereIn('orders.id', $paid()->select('orders.id'))
            ->whereIn('commissions.status', ['pending', 'paid'])
            ->selectRaw('orders.event_id as event_id, SUM(commissions.net_amount) as total')->groupBy('orders.event_id')->get() as $r) {
            $out[(int) $r->event_id]['promoter_net'] = (int) $r->total;
        }

        $sold = Ticket::query()->ofOrders($paid())
            ->join('ticket_types', 'ticket_types.id', '=', 'tickets.ticket_type_id')
            ->whereIn('tickets.status', ['sold', 'used'])
            ->toBase()->selectRaw('ticket_types.event_id as event_id, COUNT(*) as total')->groupBy('ticket_types.event_id')->get();
        foreach ($sold as $r) {
            $out[(int) $r->event_id]['sold'] = (int) $r->total;
        }

        foreach ($out as $id => $row) {
            $out[$id]['fill_rate'] = $row['capacity'] > 0 ? round($row['sold'] / $row['capacity'] * 100, 1) : null;
        }

        return $out;
    }

    /**
     * Catégories : événements créés sur la période et ventes (GMV) de la période. Deux requêtes.
     *
     * @return list<array{name: string, events: int, gmv: int}>
     */
    public function byCategory(DatePeriod $period, int $limit = 5): array
    {
        $created = DB::table('events')->leftJoin('event_categories', 'event_categories.id', '=', 'events.category_id');
        if ($period->start !== null) {
            $created->where('events.created_at', '>=', $period->startForDb());
        }
        if ($period->end !== null) {
            $created->where('events.created_at', '<=', $period->endForDb());
        }
        $events = $created->selectRaw('events.category_id as category_id, MAX(event_categories.name) as name, COUNT(*) as total')
            ->groupBy('events.category_id')->get();

        $sales = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('events', 'events.id', '=', 'orders.event_id')
            ->whereIn('orders.id', Order::query()->paid()->inPeriod($period)->select('orders.id'))
            ->selectRaw('events.category_id as category_id, SUM(order_items.total_price) as total')
            ->groupBy('events.category_id')->pluck('total', 'category_id');

        return $events->map(fn ($r) => [
            'name' => $r->name ?: 'Sans catégorie',
            'events' => (int) $r->total,
            'gmv' => (int) ($sales[$r->category_id] ?? 0),
        ])->sortByDesc('events')->take($limit)->values()->all();
    }

    private function paidOrders(array $eventIds, ?DatePeriod $period)
    {
        $query = Order::query()->paid()->whereIn('orders.event_id', $eventIds);
        if ($period !== null) {
            $query->inPeriod($period);
        }

        return $query;
    }
}
