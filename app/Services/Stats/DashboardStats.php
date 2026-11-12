<?php

namespace App\Services\Stats;

use App\Support\Stats\DatePeriod;

/**
 * Façade : compose les services de statistiques pour un périmètre et une période (écrans admin : L3).
 * Valeurs non calculables : null (affichées « — » par <x-stat-card>).
 */
class DashboardStats
{
    public function __construct(
        private readonly SalesStats $sales,
        private readonly TicketStats $tickets,
        private readonly CommissionStats $commissions,
        private readonly OrderStats $orders,
    ) {}

    public function summary(Scope $scope, ?DatePeriod $period = null): array
    {
        $period ??= DatePeriod::default();

        $byStatus = $this->orders->countsByStatus($scope, $period);

        return [
            'period' => ['key' => $period->key, 'label' => $period->label],
            'orders_paid' => $this->sales->paidOrdersCount($scope, $period),
            'gmv' => $this->sales->gmv($scope, $period),
            'collected' => $this->sales->collected($scope, $period),
            'average_basket' => $this->sales->averageBasket($scope, $period),
            'tickets_sold' => $this->tickets->sold($scope, $period),
            'tickets_scanned' => $this->tickets->scanned($scope, $period),
            'usage_rate' => $this->tickets->usageRate($scope, $period),
            'platform_revenue' => $this->commissions->platformRevenue($scope, $period),
            'promoter_net' => $this->commissions->promoterNet($scope, $period),
            'pending_payout' => $this->commissions->pendingPayout($scope, $period),
            'paid_out' => $this->commissions->paidOut($scope, $period),
            'held' => $this->commissions->held($scope, $period),
            'refunds' => $this->orders->refunds($scope, $period),
            'orders_by_status' => $byStatus,
            'orders_created' => array_sum($byStatus),
            'orders_awaiting_payment' => $byStatus['pending'] + $byStatus['processing'],
            'payment_rate' => $this->orders->paymentRate($scope, $period),
            'conversion_rate' => $this->orders->conversionRate($scope, $period),
        ];
    }
}
