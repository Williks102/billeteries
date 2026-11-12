<?php

namespace Tests\Feature\Stats;

use App\Services\Stats\DashboardStats;
use App\Services\Stats\OrderStats;
use App\Services\Stats\Scope;
use App\Support\Stats\DatePeriod;
use Tests\Concerns\StatsFixture;
use Tests\TestCase;

class OrderStatsTest extends TestCase
{
    use StatsFixture;

    private OrderStats $orders;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildStatsFixture();
        $this->orders = app(OrderStats::class);
    }

    public function test_counts_by_status_for_orders_created_in_the_period(): void
    {
        // créées en novembre 2026 : O1, O3, O4, O5, O7 payées/remboursées..., O2 pending, O6 failed
        $this->assertSame(
            ['pending' => 1, 'processing' => 0, 'paid' => 4, 'failed' => 1, 'refunded' => 1],
            $this->orders->countsByStatus(Scope::admin(), DatePeriod::default())
        );
        $this->assertSame(7, $this->orders->createdCount(Scope::admin(), DatePeriod::default()));
        $this->assertSame(
            ['pending' => 1, 'processing' => 0, 'paid' => 3, 'failed' => 1, 'refunded' => 1],
            $this->orders->countsByStatus(Scope::promoter(self::P), DatePeriod::default())
        );
        $this->assertSame(9, $this->orders->createdCount(Scope::admin(), DatePeriod::all()));
    }

    public function test_refunds_are_counted_apart(): void
    {
        // O3 : remboursée, updated_at le 07/11 ; elle n'est pas une vente
        $this->assertSame(['count' => 1, 'amount' => 10000], $this->orders->refunds(Scope::admin(), DatePeriod::default()));
        $this->assertSame(['count' => 0, 'amount' => 0], $this->orders->refunds(Scope::admin(), DatePeriod::preset('last_month')));
        $this->assertSame(['count' => 0, 'amount' => 0], $this->orders->refunds(Scope::promoter(self::P2), DatePeriod::all()));
    }

    public function test_payment_rate_is_paid_over_created(): void
    {
        $this->assertSame(57.1, $this->orders->paymentRate(Scope::admin(), DatePeriod::default()));            // 4 / 7
        $this->assertSame(50.0, $this->orders->paymentRate(Scope::promoter(self::P), DatePeriod::default())); // 3 / 6
        $this->assertNull($this->orders->paymentRate(Scope::promoter(self::P2), DatePeriod::preset('last_month'))); // aucune commande créée
    }

    public function test_conversion_rate_is_null_because_visits_are_not_tracked(): void
    {
        $this->assertNull($this->orders->conversionRate(Scope::admin(), DatePeriod::default()));
        $this->assertNull($this->orders->conversionRate(Scope::promoter(self::P), DatePeriod::all()));
        $this->assertNull(app(DashboardStats::class)->summary(Scope::admin())['conversion_rate']);
    }

    public function test_dashboard_facade_composes_the_services(): void
    {
        $s = app(DashboardStats::class)->summary(Scope::promoter(self::P));

        $this->assertSame('this_month', $s['period']['key']);
        $this->assertSame(3, $s['orders_paid']);
        $this->assertSame(35000, $s['gmv']);
        $this->assertSame(11667, $s['average_basket']);
        $this->assertSame(5, $s['tickets_sold']);
        $this->assertSame(1, $s['tickets_scanned']);
        $this->assertSame(20.0, $s['usage_rate']);
        $this->assertSame(2100, $s['platform_revenue']);
        $this->assertSame(32900, $s['promoter_net']);
        $this->assertSame(50.0, $s['payment_rate']);
    }
}
