<?php

namespace App\Services\Stats;

use App\Models\Order;
use App\Support\Stats\DatePeriod;

/**
 * Commandes par statut de paiement. Les comptes par statut portent sur les commandes CRÉÉES dans la
 * période (created_at). Le taux de paiement = payées / créées (proxy honnête : il n'existe aucun comptage
 * de visites, donc la conversion n'est pas calculable et vaut null).
 */
class OrderStats
{
    public const STATUSES = ['pending', 'processing', 'paid', 'failed', 'refunded'];

    /** @return array<string, int> une clé par statut connu (0 par défaut) */
    public function countsByStatus(Scope $scope, DatePeriod $period): array
    {
        $rows = Order::query()->forScope($scope)->inPeriod($period, 'created')
            ->toBase()->selectRaw('orders.payment_status as status, COUNT(*) as total')
            ->groupBy('orders.payment_status')->pluck('total', 'status');

        $counts = array_fill_keys(self::STATUSES, 0);
        foreach ($rows as $status => $total) {
            $counts[$status] = (int) $total;
        }

        return $counts;
    }

    public function createdCount(Scope $scope, DatePeriod $period): int
    {
        return array_sum($this->countsByStatus($scope, $period));
    }

    /**
     * Remboursements (commandes `refunded`, datées par updated_at), comptés à part des ventes.
     *
     * @return array{count: int, amount: int}
     */
    public function refunds(Scope $scope, DatePeriod $period): array
    {
        $query = Order::query()->where('payment_status', 'refunded')->forScope($scope)->inPeriod($period, 'updated');

        return ['count' => $query->count(), 'amount' => (int) $query->sum('orders.total_amount')];
    }

    /** Taux de paiement (%) = payées / créées, 1 décimale ; null sans commande créée. */
    public function paymentRate(Scope $scope, DatePeriod $period): ?float
    {
        $counts = $this->countsByStatus($scope, $period);
        $created = array_sum($counts);

        return $created > 0 ? round($counts['paid'] / $created * 100, 1) : null;
    }

    /** Taux de conversion : NON calculable (aucun comptage de visites) => toujours null. */
    public function conversionRate(Scope $scope, DatePeriod $period): ?float
    {
        return null;
    }
}
