<?php

namespace Tests\Feature\Stats;

use App\Services\Stats\Scope;
use App\Services\Stats\TicketStats;
use App\Support\Stats\DatePeriod;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\StatsFixture;
use Tests\TestCase;

class TicketStatsTest extends TestCase
{
    use StatsFixture;

    private TicketStats $tickets;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildStatsFixture();
        $this->tickets = app(TicketStats::class);
    }

    public function test_sold_is_sold_plus_used_of_paid_orders_only(): void
    {
        $nov = DatePeriod::default();

        // novembre, P : O1 (1 sold + 1 used) + O4 (2 sold) + O5 (1 sold gratuit) = 5 ; O2 reserved, O3/O6 cancelled exclus
        $this->assertSame(5, $this->tickets->sold(Scope::promoter(self::P), $nov));
        $this->assertSame(1, $this->tickets->scanned(Scope::promoter(self::P), $nov));
        $this->assertSame(6, $this->tickets->sold(Scope::admin(), $nov)); // + O7
        // toutes périodes : + O8 + O9
        $this->assertSame(7, $this->tickets->sold(Scope::promoter(self::P)));
        $this->assertSame(8, $this->tickets->sold(Scope::admin()));
        $this->assertSame(1, $this->tickets->sold(Scope::promoter(self::P), DatePeriod::preset('last_month')));
    }

    public function test_a_ticket_linked_by_pivot_and_item_is_counted_once(): void
    {
        // O5 : billet gratuit lié par order_tickets ET order_item_id
        $this->assertSame(1, $this->tickets->sold(Scope::event(1), DatePeriod::custom('2026-11-12', '2026-11-12')));
        // O4 : liés par order_item_id uniquement (aucun pivot) ; O1 : pivot uniquement
        $this->assertSame(2, $this->tickets->sold(Scope::event(1), DatePeriod::custom('2026-11-10', '2026-11-10')));
        $this->assertSame(2, $this->tickets->sold(Scope::event(1), DatePeriod::custom('2026-11-03', '2026-11-03')));
    }

    public function test_free_order_tickets_count_as_sold(): void
    {
        $this->assertSame(1, $this->tickets->sold(Scope::event(1), DatePeriod::custom('2026-11-12', '2026-11-12')));
    }

    public function test_reserved_cancelled_and_refunded_are_not_sold(): void
    {
        $this->assertSame(1, $this->tickets->reserved(Scope::promoter(self::P))); // O2
        $this->assertSame(0, $this->tickets->sold(Scope::event(1), DatePeriod::custom('2026-11-05', '2026-11-07')));
        $this->assertSame(0, $this->tickets->reserved(Scope::promoter(self::P2)));
    }

    public function test_capacity_available_and_fill_rate(): void
    {
        $p = Scope::promoter(self::P);

        $this->assertSame(170, $this->tickets->capacity($p));          // 100 + 50 + 20
        $this->assertSame(163, $this->tickets->available($p));         // 170 - 7 vendus (le billet réservé reste « disponible »)
        $this->assertSame(4.1, $this->tickets->fillRate($p));          // 7 / 170 = 4,1176
        $this->assertSame(180, $this->tickets->capacity(Scope::admin()));
        $this->assertSame(172, $this->tickets->available(Scope::admin()));
        $this->assertSame(4.4, $this->tickets->fillRate(Scope::admin())); // 8 / 180 = 4,44
        $this->assertSame(10, $this->tickets->capacity(Scope::promoter(self::P2)));
    }

    public function test_usage_rate_is_scanned_over_sold_including_used(): void
    {
        $this->assertSame(20.0, $this->tickets->usageRate(Scope::promoter(self::P), DatePeriod::default())); // 1 / 5
        $this->assertSame(16.7, $this->tickets->usageRate(Scope::admin(), DatePeriod::default()));            // 1 / 6
        $this->assertSame(14.3, $this->tickets->usageRate(Scope::promoter(self::P)));                         // 1 / 7
    }

    public function test_not_computable_values_are_null(): void
    {
        $this->assertNull($this->tickets->usageRate(Scope::promoter(self::P2), DatePeriod::preset('last_month'))); // aucun billet vendu
        $this->assertNull($this->tickets->capacity(Scope::buyer(self::BUYER)));
        $this->assertNull($this->tickets->available(Scope::buyer(self::BUYER)));
        $this->assertNull($this->tickets->fillRate(Scope::buyer(self::BUYER)));
        $this->assertNull($this->tickets->fillRate(Scope::promoter(999))); // capacité 0 : pas de division
    }

    public function test_buyer_scope_counts_only_own_tickets(): void
    {
        $this->assertSame(7, $this->tickets->sold(Scope::buyer(self::BUYER)));   // tout sauf O7
        $this->assertSame(1, $this->tickets->sold(Scope::buyer(self::BUYER2)));  // O7
    }

    public function test_fixed_number_of_queries(): void
    {
        DB::enableQueryLog();
        $this->tickets->sold(Scope::admin(), DatePeriod::default());
        $this->tickets->usageRate(Scope::promoter(self::P), DatePeriod::default());
        $n = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(3, $n); // 1 (sold) + 2 (scanned, sold) : indépendant du nombre de billets
    }
}
