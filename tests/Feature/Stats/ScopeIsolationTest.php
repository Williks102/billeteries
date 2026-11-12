<?php

namespace Tests\Feature\Stats;

use App\Services\Stats\CommissionStats;
use App\Services\Stats\OrderStats;
use App\Services\Stats\SalesStats;
use App\Services\Stats\Scope;
use App\Services\Stats\TicketStats;
use App\Support\Stats\DatePeriod;
use Tests\Concerns\StatsFixture;
use Tests\TestCase;

/** Un promoteur ne voit JAMAIS les ventes d'un autre promoteur ; un acheteur ne voit que ses commandes. */
class ScopeIsolationTest extends TestCase
{
    use StatsFixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildStatsFixture();
    }

    public function test_promoter_p_never_sees_p2_sales(): void
    {
        $sales = app(SalesStats::class);
        $all = DatePeriod::all();

        $ids = $sales->paidOrders(Scope::promoter(self::P), $all)->pluck('id')->all();
        $this->assertNotContains($this->o['O7'], $ids);
        $this->assertEqualsCanonicalizing([$this->o['O1'], $this->o['O4'], $this->o['O5'], $this->o['O8'], $this->o['O9']], $ids);

        $idsP2 = $sales->paidOrders(Scope::promoter(self::P2), $all)->pluck('id')->all();
        $this->assertSame([$this->o['O7']], $idsP2);
        $this->assertSame(20000, $sales->gmv(Scope::promoter(self::P2), $all));
        $this->assertSame(50000, $sales->gmv(Scope::promoter(self::P), $all));
    }

    public function test_every_service_is_isolated_by_scope(): void
    {
        $all = DatePeriod::all();
        $p = Scope::promoter(self::P);
        $p2 = Scope::promoter(self::P2);

        // billets
        $this->assertSame(1, app(TicketStats::class)->sold($p2));
        $this->assertSame(7, app(TicketStats::class)->sold($p));
        // commissions
        $this->assertSame(18800, app(CommissionStats::class)->promoterNet($p2, $all));
        $this->assertSame(47000, app(CommissionStats::class)->promoterNet($p, $all));
        // commandes
        $this->assertSame(1, app(OrderStats::class)->createdCount($p2, $all));
        $this->assertSame(8, app(OrderStats::class)->createdCount($p, $all));
        // somme des promoteurs = admin
        $this->assertSame(
            app(SalesStats::class)->gmv(Scope::admin(), $all),
            app(SalesStats::class)->gmv($p, $all) + app(SalesStats::class)->gmv($p2, $all)
        );
    }

    public function test_unknown_promoter_sees_nothing(): void
    {
        $this->assertSame(0, app(SalesStats::class)->paidOrdersCount(Scope::promoter(999), DatePeriod::all()));
        $this->assertSame(0, app(CommissionStats::class)->platformRevenue(Scope::promoter(999), DatePeriod::all()));
    }

    public function test_event_and_buyer_scopes(): void
    {
        $sales = app(SalesStats::class);
        $all = DatePeriod::all();

        $this->assertSame(50000, $sales->gmv(Scope::event(1), $all));
        $this->assertSame(20000, $sales->gmv(Scope::event(2), $all));
        $this->assertSame(50000, $sales->gmv(Scope::buyer(self::BUYER), $all));
        $this->assertSame(20000, $sales->gmv(Scope::buyer(self::BUYER2), $all));
        $this->assertSame(0, $sales->gmv(Scope::buyer(12345), $all));
    }
}
