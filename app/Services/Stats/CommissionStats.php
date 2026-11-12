<?php

namespace App\Services\Stats;

use App\Models\Commission;
use App\Models\Order;
use App\Support\Stats\DatePeriod;

/**
 * Commissions (décision D1) : seules comptent les commissions de commandes `paid` (statuts commission
 * pending/paid). `held` (commande remboursée) est présenté à part ; `cancelled` (commande échouée) est exclu.
 * Revenu plateforme = commission seule. Revenu promoteur = net_amount. Commandes gratuites : aucune commission.
 * Période = date de vente de la commande.
 */
class CommissionStats
{
    /** Revenu plateforme : SUM(commission_amount) des commandes payées. */
    public function platformRevenue(Scope $scope, DatePeriod $period): int
    {
        return (int) $this->counted($scope, $period)->sum('commission_amount');
    }

    /** Revenu net promoteur : SUM(net_amount) des commandes payées. */
    public function promoterNet(Scope $scope, DatePeriod $period): int
    {
        return (int) $this->counted($scope, $period)->sum('net_amount');
    }

    /** Net promoteur en attente de versement (commission pending). */
    public function pendingPayout(Scope $scope, DatePeriod $period): int
    {
        return (int) $this->counted($scope, $period)->where('status', 'pending')->sum('net_amount');
    }

    /** Net promoteur déjà versé (commission paid). */
    public function paidOut(Scope $scope, DatePeriod $period): int
    {
        return (int) $this->counted($scope, $period)->where('status', 'paid')->sum('net_amount');
    }

    /**
     * Commissions suspendues (commandes remboursées), à part : jamais dans les revenus.
     *
     * @return array{commission: int, net: int}
     */
    public function held(Scope $scope, DatePeriod $period): array
    {
        $query = Commission::query()
            ->whereIn('order_id', Order::query()->forScope($scope)->inPeriod($period)->select('orders.id'))
            ->where('status', 'held');

        return [
            'commission' => (int) $query->clone()->sum('commission_amount'),
            'net' => (int) $query->clone()->sum('net_amount'),
        ];
    }

    private function counted(Scope $scope, DatePeriod $period)
    {
        return Commission::query()
            ->whereIn('commissions.order_id', Order::query()->paid()->forScope($scope)->inPeriod($period)->select('orders.id'))
            ->whereIn('commissions.status', ['pending', 'paid']);
    }
}
