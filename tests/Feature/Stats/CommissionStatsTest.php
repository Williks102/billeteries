<?php

namespace Tests\Feature\Stats;

use App\Services\Stats\CommissionStats;
use App\Services\Stats\Scope;
use App\Support\Stats\DatePeriod;
use Tests\Concerns\StatsFixture;
use Tests\TestCase;

/**
 * Commission 6 % de la somme des lignes. D1 : seules les commissions de commandes `paid` comptent ;
 * held (O3 remboursée) à part ; cancelled (O6 échouée) exclue ; commande gratuite O5 : aucune commission.
 */
class CommissionStatsTest extends TestCase
{
    use StatsFixture;

    private CommissionStats $c;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildStatsFixture();
        $this->c = app(CommissionStats::class);
    }

    public function test_promoter_p_november(): void
    {
        $p = Scope::promoter(self::P);
        $nov = DatePeriod::default();

        $this->assertSame(2100, $this->c->platformRevenue($p, $nov)); // O1 1 200 + O4 900 (O3 held et O6 cancelled exclues)
        $this->assertSame(32900, $this->c->promoterNet($p, $nov));    // 18 800 + 14 100
        $this->assertSame(18800, $this->c->pendingPayout($p, $nov));  // O1 pending
        $this->assertSame(14100, $this->c->paidOut($p, $nov));        // O4 versée
        $this->assertSame(['commission' => 600, 'net' => 9400], $this->c->held($p, $nov)); // O3, à part
    }

    public function test_admin_november_and_all_time(): void
    {
        $nov = DatePeriod::default();

        $this->assertSame(3300, $this->c->platformRevenue(Scope::admin(), $nov)); // + O7 1 200
        $this->assertSame(51700, $this->c->promoterNet(Scope::admin(), $nov));
        $this->assertSame(37600, $this->c->pendingPayout(Scope::admin(), $nov));
        $this->assertSame(14100, $this->c->paidOut(Scope::admin(), $nov));

        $all = DatePeriod::all();
        $p = Scope::promoter(self::P);
        $this->assertSame(3000, $this->c->platformRevenue($p, $all)); // 1 200 + 900 + 600 (O8) + 300 (O9)
        $this->assertSame(47000, $this->c->promoterNet($p, $all));
        $this->assertSame(32900, $this->c->pendingPayout($p, $all));
        $this->assertSame(14100, $this->c->paidOut($p, $all));
        $this->assertSame($this->c->promoterNet($p, $all), $this->c->pendingPayout($p, $all) + $this->c->paidOut($p, $all));
    }

    public function test_platform_revenue_is_commission_only_not_the_sales(): void
    {
        // le revenu plateforme (2 100) est bien la commission (6 % de 35 000), pas le GMV (35 000)
        $p = Scope::promoter(self::P);
        $this->assertSame(2100, $this->c->platformRevenue($p, DatePeriod::default()));
        $this->assertSame(2100, (int) round(0.06 * 35000));
    }

    public function test_free_order_brings_no_commission(): void
    {
        $day = DatePeriod::custom('2026-11-12', '2026-11-12'); // O5 seule
        $this->assertSame(0, $this->c->platformRevenue(Scope::event(1), $day));
        $this->assertSame(0, $this->c->promoterNet(Scope::event(1), $day));
    }

    public function test_cancelled_and_held_never_count_as_revenue(): void
    {
        $day = DatePeriod::custom('2026-11-06', '2026-11-07'); // O3 refunded (held) + O6 failed (cancelled)
        $this->assertSame(0, $this->c->platformRevenue(Scope::admin(), $day));
        $this->assertSame(0, $this->c->promoterNet(Scope::admin(), $day));
        $this->assertSame(600, $this->c->held(Scope::admin(), $day)['commission']);
    }

    public function test_other_months_and_years_are_excluded(): void
    {
        $this->assertSame(600, $this->c->platformRevenue(Scope::admin(), DatePeriod::preset('last_month'))); // O8
        $this->assertSame(0, $this->c->platformRevenue(Scope::promoter(self::P2), DatePeriod::preset('last_month')));
    }

    public function test_promoter_p2_sees_only_their_own(): void
    {
        $this->assertSame(1200, $this->c->platformRevenue(Scope::promoter(self::P2), DatePeriod::all()));
        $this->assertSame(18800, $this->c->promoterNet(Scope::promoter(self::P2), DatePeriod::all()));
    }
}
