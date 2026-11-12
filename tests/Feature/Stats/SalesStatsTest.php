<?php

namespace Tests\Feature\Stats;

use App\Services\Stats\SalesStats;
use App\Services\Stats\Scope;
use App\Support\Stats\DatePeriod;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\StatsFixture;
use Tests\TestCase;

/** Valeurs cibles recalculées à la main sur le jeu canonique (voir tests/Concerns/StatsFixture.php). */
class SalesStatsTest extends TestCase
{
    use StatsFixture;

    private SalesStats $sales;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildStatsFixture();
        $this->sales = app(SalesStats::class);
    }

    public function test_admin_november_2026(): void
    {
        $p = DatePeriod::default();
        $s = Scope::admin();

        // payées en novembre : O1, O4, O5 (gratuite), O7 ; exclues : O2 pending, O3 refunded, O6 failed, O8 (oct.), O9 (2025)
        $this->assertSame(4, $this->sales->paidOrdersCount($s, $p));
        $this->assertSame(55000, $this->sales->gmv($s, $p));       // 20 000 + 15 000 + 0 + 20 000
        $this->assertSame(55000, $this->sales->collected($s, $p));
        $this->assertSame(13750, $this->sales->averageBasket($s, $p)); // 55 000 / 4
    }

    public function test_promoter_p_november_2026_counts_the_two_line_order_once(): void
    {
        $p = DatePeriod::default();
        $s = Scope::promoter(self::P);

        $this->assertSame(3, $this->sales->paidOrdersCount($s, $p)); // O1, O4, O5
        $this->assertSame(35000, $this->sales->gmv($s, $p));          // O4 = 15 000 (une seule fois malgré 2 lignes)
        $this->assertSame(35000, $this->sales->collected($s, $p));
        $this->assertSame(11667, $this->sales->averageBasket($s, $p)); // 35 000 / 3 = 11 666,67
    }

    public function test_other_periods(): void
    {
        $admin = Scope::admin();
        $p = Scope::promoter(self::P);

        $this->assertSame([1, 10000], [$this->sales->paidOrdersCount($admin, DatePeriod::preset('last_month')), $this->sales->gmv($admin, DatePeriod::preset('last_month'))]);
        $this->assertSame([5, 65000], [$this->sales->paidOrdersCount($admin, DatePeriod::preset('this_year')), $this->sales->gmv($admin, DatePeriod::preset('this_year'))]);
        $this->assertSame([6, 70000], [$this->sales->paidOrdersCount($admin, DatePeriod::all()), $this->sales->gmv($admin, DatePeriod::all())]);
        $this->assertSame([5, 50000], [$this->sales->paidOrdersCount($p, DatePeriod::all()), $this->sales->gmv($p, DatePeriod::all())]);
        $this->assertSame([4, 45000], [$this->sales->paidOrdersCount($p, DatePeriod::preset('this_year')), $this->sales->gmv($p, DatePeriod::preset('this_year'))]);
        $this->assertSame(0, $this->sales->paidOrdersCount($admin, DatePeriod::custom('2026-11-13', '2026-11-14')));
    }

    public function test_period_excludes_other_months_and_other_years(): void
    {
        // O8 (20 octobre 2026) et O9 (20 novembre 2025, même numéro de mois que novembre 2026) sont hors du mois courant.
        $november = $this->sales->paidOrders(Scope::admin(), DatePeriod::default())->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$this->o['O1'], $this->o['O4'], $this->o['O5'], $this->o['O7']], $november);
        $this->assertNotContains($this->o['O8'], $november);
        $this->assertNotContains($this->o['O9'], $november);
    }

    public function test_free_order_is_a_paid_order_with_zero_revenue(): void
    {
        $s = Scope::event(1);
        $day = DatePeriod::custom('2026-11-12', '2026-11-12');

        $this->assertSame([1, 0, 0], [$this->sales->paidOrdersCount($s, $day), $this->sales->gmv($s, $day), $this->sales->collected($s, $day)]);
        $this->assertSame(0, $this->sales->averageBasket($s, $day)); // une commande, 0 FCFA : panier moyen 0 (pas null)
        // et elle abaisse le panier moyen de novembre : sans elle 35 000 / 2 = 17 500 ; avec : 11 667
        $this->assertSame(11667, $this->sales->averageBasket(Scope::promoter(self::P), DatePeriod::default()));
    }

    public function test_pending_refunded_and_failed_orders_are_never_sales(): void
    {
        $day = DatePeriod::custom('2026-11-05', '2026-11-07'); // O2 pending, O3 refunded, O6 failed

        $this->assertSame(0, $this->sales->paidOrdersCount(Scope::admin(), $day));
        $this->assertSame(0, $this->sales->gmv(Scope::admin(), $day));
        $this->assertNull($this->sales->averageBasket(Scope::admin(), $day));
    }

    public function test_sale_date_is_paid_at_with_created_at_fallback(): void
    {
        // créée le 31/10 à 23:30, payée le 01/11 à 00:10 : vendue en NOVEMBRE
        $this->extraPaid('X1', 7000, '2026-10-31 23:30:00', '2026-11-01 00:10:00');
        // ancienne commande payée sans paid_at : repli sur created_at (14/11)
        $this->extraPaid('X2', 3000, '2026-11-14 10:00:00', null);

        $p = Scope::event(1);
        $this->assertSame(5, $this->sales->paidOrdersCount($p, DatePeriod::default())); // 3 + X1 + X2
        $this->assertSame(35000 + 7000 + 3000, $this->sales->gmv($p, DatePeriod::default()));
        $this->assertSame(0, $this->sales->paidOrdersCount($p, DatePeriod::custom('2026-10-31', '2026-10-31')));
    }

    public function test_historical_order_with_old_fee_is_based_on_item_sum(): void
    {
        // commande historique : total_amount = lignes + 500 de frais d'avant leur suppression
        $id = $this->extraPaid('H1', 10000, '2026-11-14 10:00:00', '2026-11-14 10:00:00');
        DB::table('orders')->where('id', $id)->update(['total_amount' => 10500]);

        $day = DatePeriod::custom('2026-11-14', '2026-11-14');
        $this->assertSame(10000, $this->sales->gmv(Scope::event(1), $day));      // base = lignes
        $this->assertSame(10500, $this->sales->collected(Scope::event(1), $day)); // encaissé client = total_amount
    }

    public function test_series_by_day_is_bounded_and_zero_filled(): void
    {
        $rows = $this->sales->byDay(Scope::admin(), DatePeriod::default());

        $this->assertCount(30, $rows); // 1er au 30 novembre, un point par jour
        $this->assertSame('2026-11-01', $rows[0]['date']);
        $this->assertSame('2026-11-30', $rows[29]['date']);
        $byDate = array_column($rows, null, 'date');
        $this->assertSame(['orders' => 1, 'gmv' => 20000, 'collected' => 20000], array_intersect_key($byDate['2026-11-03'], array_flip(['orders', 'gmv', 'collected'])));
        $this->assertSame(1, $byDate['2026-11-10']['orders']);
        $this->assertSame(15000, $byDate['2026-11-10']['gmv']); // O4 deux lignes, une fois
        $this->assertSame(['orders' => 1, 'gmv' => 0, 'collected' => 0], array_intersect_key($byDate['2026-11-12'], array_flip(['orders', 'gmv', 'collected'])));
        $this->assertSame(0, $byDate['2026-11-04']['orders']);
        $this->assertSame(55000, array_sum(array_column($rows, 'gmv')));
        $this->assertSame(4, array_sum(array_column($rows, 'orders')));
    }

    public function test_series_by_month_over_the_year_and_all_time(): void
    {
        $year = $this->sales->byMonth(Scope::admin(), DatePeriod::preset('this_year'));
        $this->assertCount(12, $year);
        $byMonth = array_column($year, null, 'date');
        $this->assertSame(10000, $byMonth['2026-10']['gmv']);
        $this->assertSame(55000, $byMonth['2026-11']['gmv']);
        $this->assertSame(0, $byMonth['2026-12']['orders']);
        $this->assertSame(65000, array_sum(array_column($year, 'gmv'))); // O9 (2025) hors période

        $all = $this->sales->byMonth(Scope::admin(), DatePeriod::all());
        $this->assertSame('2025-11', $all[0]['date']);      // première vente
        $this->assertSame('2026-11', end($all)['date']);    // dernière vente
        $this->assertCount(13, $all);
        $this->assertSame(70000, array_sum(array_column($all, 'gmv')));
    }

    public function test_series_without_sales_and_unbounded_is_empty(): void
    {
        $this->assertSame([], $this->sales->byDay(Scope::promoter(999), DatePeriod::all()));
    }

    public function test_no_query_per_day_or_per_order(): void
    {
        DB::enableQueryLog();
        $this->sales->byDay(Scope::admin(), DatePeriod::default());
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(2, $count, 'une série = 2 requêtes quel que soit le nombre de jours');
    }

    public function test_stats_never_write(): void
    {
        $before = [DB::table('orders')->count(), DB::table('tickets')->count(), DB::table('commissions')->count(), DB::table('ticket_types')->sum('quantity_sold')];
        $this->sales->byDay(Scope::admin(), DatePeriod::all());
        $this->sales->gmv(Scope::admin(), DatePeriod::all());

        $this->assertSame($before, [DB::table('orders')->count(), DB::table('tickets')->count(), DB::table('commissions')->count(), DB::table('ticket_types')->sum('quantity_sold')]);
    }

    private function extraPaid(string $key, int $total, string $created, ?string $paidAt): int
    {
        $id = DB::table('orders')->insertGetId([
            'user_id' => 1, 'event_id' => 1, 'total_amount' => $total, 'payment_status' => 'paid', 'order_number' => 'ORD-X-'.$key,
            'paid_at' => $paidAt, 'created_at' => $created, 'updated_at' => $created,
        ]);
        DB::table('order_items')->insert(['order_id' => $id, 'ticket_type_id' => 1, 'quantity' => 1, 'unit_price' => $total, 'total_price' => $total]);

        return $id;
    }
}
