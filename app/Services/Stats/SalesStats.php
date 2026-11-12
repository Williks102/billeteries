<?php

namespace App\Services\Stats;

use App\Models\Order;
use App\Support\Stats\DatePeriod;
use App\Support\Stats\SqlDate;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Ventes = commandes `paid` (dont les commandes gratuites à 0 FCFA), datées par paid_at (repli created_at).
 * Montants en FCFA entiers. GMV = somme des lignes (order_items.total_price) : aucune dépendance aux
 * anciens frais de 500 FCFA que total_amount peut encore contenir sur des commandes historiques.
 * Lecture seule ; agrégation SQL ; aucune requête par jour ou par mois.
 */
class SalesStats
{
    /** Requête de base : commandes payées du périmètre et de la période. */
    public function paidOrders(Scope $scope, DatePeriod $period): Builder
    {
        return Order::query()->paid()->forScope($scope)->inPeriod($period);
    }

    public function paidOrdersCount(Scope $scope, DatePeriod $period): int
    {
        return $this->paidOrders($scope, $period)->count();
    }

    /** Chiffre d'affaires brut (GMV) : SUM(order_items.total_price) des commandes payées. */
    public function gmv(Scope $scope, DatePeriod $period): int
    {
        return (int) DB::table('order_items')
            ->whereIn('order_id', $this->paidOrders($scope, $period)->select('orders.id'))
            ->sum('total_price');
    }

    /** Encaissé client : SUM(orders.total_amount) des commandes payées. */
    public function collected(Scope $scope, DatePeriod $period): int
    {
        return (int) $this->paidOrders($scope, $period)->sum('orders.total_amount');
    }

    /** Panier moyen = GMV / commandes payées (les gratuites comptent) ; null sans commande. */
    public function averageBasket(Scope $scope, DatePeriod $period): ?int
    {
        $count = $this->paidOrdersCount($scope, $period);

        return $count > 0 ? (int) round($this->gmv($scope, $period) / $count) : null;
    }

    /**
     * Série par jour, bornée à la période (jours sans vente à 0). Période « all » : de la première
     * à la dernière vente. Deux requêtes au total.
     *
     * @return list<array{date: string, orders: int, gmv: int, collected: int}>
     */
    public function byDay(Scope $scope, DatePeriod $period): array
    {
        return $this->series($scope, $period, 'day');
    }

    /** @return list<array{date: string, orders: int, gmv: int, collected: int}> clés `AAAA-MM` */
    public function byMonth(Scope $scope, DatePeriod $period): array
    {
        return $this->series($scope, $period, 'month');
    }

    private function series(Scope $scope, DatePeriod $period, string $unit): array
    {
        $bucket = $unit === 'day' ? SqlDate::day(SqlDate::SALE_REF) : SqlDate::month(SqlDate::SALE_REF);

        $orders = $this->paidOrders($scope, $period);

        $perBucket = $orders->clone()->toBase()
            ->selectRaw("{$bucket} as bucket, COUNT(*) as orders_count, SUM(orders.total_amount) as collected")
            ->groupBy('bucket')
            ->get()->keyBy('bucket');

        $gmv = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.id', $orders->clone()->select('orders.id'))
            ->selectRaw("{$bucket} as bucket, SUM(order_items.total_price) as gmv")
            ->groupBy('bucket')
            ->pluck('gmv', 'bucket');

        $keys = $perBucket->keys()->sort()->values();
        if ($period->isBounded()) {
            $from = $period->start;
            $to = $period->end;
        } elseif ($keys->isNotEmpty()) {
            $from = CarbonImmutable::parse($keys->first().($unit === 'month' ? '-01' : ''));
            $to = CarbonImmutable::parse($keys->last().($unit === 'month' ? '-01' : ''));
        } else {
            return [];
        }

        $format = $unit === 'day' ? 'Y-m-d' : 'Y-m';
        $cursor = $unit === 'day' ? $from->startOfDay() : $from->startOfMonth();
        $last = $unit === 'day' ? $to->startOfDay() : $to->startOfMonth();

        $rows = [];
        while ($cursor->lessThanOrEqualTo($last)) {
            $key = $cursor->format($format);
            $rows[] = [
                'date' => $key,
                'orders' => (int) ($perBucket[$key]->orders_count ?? 0),
                'gmv' => (int) ($gmv[$key] ?? 0),
                'collected' => (int) ($perBucket[$key]->collected ?? 0),
            ];
            $cursor = $unit === 'day' ? $cursor->addDay() : $cursor->addMonthNoOverflow();
        }

        return $rows;
    }
}
