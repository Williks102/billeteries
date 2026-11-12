<?php

namespace App\Services\Stats;

use App\Support\Stats\DatePeriod;

/**
 * Façade minimale : compose les services de statistiques pour un périmètre et une période.
 * Aucun écran n'en dépend encore (lots L3-L5). Valeurs non calculables : null.
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
            'orders_by_status' => $this->orders->countsByStatus($scope, $period),
            'payment_rate' => $this->orders->paymentRate($scope, $period),
            'conversion_rate' => $this->orders->conversionRate($scope, $period),
        ];
    }
}
