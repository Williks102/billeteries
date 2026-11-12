<?php

namespace Tests\Unit\Stats;

use App\Support\Stats\DatePeriod;
use App\Support\Stats\InvalidPeriodException;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class DatePeriodTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function now(string $when = '2026-11-15 12:00:00'): Carbon
    {
        return Carbon::parse($when, 'Africa/Abidjan');
    }

    private function fmt(DatePeriod $p): array
    {
        return [$p->start?->format('Y-m-d H:i:s.u'), $p->end?->format('Y-m-d H:i:s.u')];
    }

    public function test_default_is_the_current_calendar_month_in_abidjan(): void
    {
        $p = DatePeriod::default($this->now());

        $this->assertSame('this_month', $p->key);
        $this->assertSame(['2026-11-01 00:00:00.000000', '2026-11-30 23:59:59.999999'], $this->fmt($p));
        $this->assertSame('Africa/Abidjan', $p->start->getTimezone()->getName());
        $this->assertSame($this->fmt($p), $this->fmt(DatePeriod::from(Request::create('/'), $this->now())));
    }

    public function test_presets_bounds(): void
    {
        $now = $this->now();

        $this->assertSame(['2026-11-15 00:00:00.000000', '2026-11-15 23:59:59.999999'], $this->fmt(DatePeriod::preset('today', $now)));
        $this->assertSame(['2026-11-09 00:00:00.000000', '2026-11-15 23:59:59.999999'], $this->fmt(DatePeriod::preset('7d', $now)));
        $this->assertSame(['2026-10-17 00:00:00.000000', '2026-11-15 23:59:59.999999'], $this->fmt(DatePeriod::preset('30d', $now)));
        $this->assertSame(['2026-10-01 00:00:00.000000', '2026-10-31 23:59:59.999999'], $this->fmt(DatePeriod::preset('last_month', $now)));
        $this->assertSame(['2026-01-01 00:00:00.000000', '2026-12-31 23:59:59.999999'], $this->fmt(DatePeriod::preset('this_year', $now)));
        $this->assertSame([null, null], $this->fmt(DatePeriod::all()));
        $this->assertFalse(DatePeriod::all()->isBounded());
        $this->assertTrue(DatePeriod::default($now)->isBounded());
    }

    public function test_last_month_in_january_is_december_of_the_previous_year_and_handles_short_months(): void
    {
        $this->assertSame(['2025-12-01 00:00:00.000000', '2025-12-31 23:59:59.999999'], $this->fmt(DatePeriod::preset('last_month', $this->now('2026-01-31 08:00:00'))));
        $this->assertSame(['2026-02-01 00:00:00.000000', '2026-02-28 23:59:59.999999'], $this->fmt(DatePeriod::preset('last_month', $this->now('2026-03-31 08:00:00'))));
    }

    public function test_timezone_is_abidjan_whatever_the_input_timezone(): void
    {
        // 2026-11-15 23:30 à Los Angeles (UTC-8) = 2026-11-16 07:30 à Abidjan (UTC+0) : « aujourd'hui » = le 16.
        $now = Carbon::parse('2026-11-15 23:30:00', 'America/Los_Angeles');

        $this->assertSame(['2026-11-16 00:00:00.000000', '2026-11-16 23:59:59.999999'], $this->fmt(DatePeriod::preset('today', $now)));
    }

    public function test_default_uses_the_test_clock(): void
    {
        Carbon::setTestNow($this->now('2027-02-10 09:00:00'));

        $this->assertSame(['2027-02-01 00:00:00.000000', '2027-02-28 23:59:59.999999'], $this->fmt(DatePeriod::default()));
    }

    public function test_custom_range_includes_the_end_day_entirely(): void
    {
        $p = DatePeriod::from(Request::create('/', 'GET', ['start_date' => '2026-11-01', 'end_date' => '2026-11-30']), $this->now());

        $this->assertSame('custom', $p->key);
        $this->assertSame(['2026-11-01 00:00:00.000000', '2026-11-30 23:59:59.999999'], $this->fmt($p));
        $this->assertSame('2026-11-30 23:59:59.999999', $p->endForDb());
        $this->assertSame('2026-11-01 00:00:00', $p->startForDb());
        $this->assertSame('Du 01/11/2026 au 30/11/2026', $p->label);
    }

    public function test_same_day_custom_range_is_valid(): void
    {
        $p = DatePeriod::custom('2026-11-10', '2026-11-10');

        $this->assertSame(['2026-11-10 00:00:00.000000', '2026-11-10 23:59:59.999999'], $this->fmt($p));
    }

    public function test_month_is_not_the_same_as_year(): void
    {
        $month = DatePeriod::preset('this_month', $this->now());
        $year = DatePeriod::preset('this_year', $this->now());
        $lastYearNov = DatePeriod::custom('2025-11-01', '2025-11-30');

        $this->assertSame(11, $month->start->month);
        $this->assertSame(2026, $month->start->year);
        $this->assertTrue($lastYearNov->end->lessThan($month->start), 'novembre 2025 est hors du mois civil 2026');
        $this->assertTrue($lastYearNov->end->lessThan($year->start));
    }

    public function test_start_after_end_is_refused(): void
    {
        $this->expectException(InvalidPeriodException::class);
        DatePeriod::from(Request::create('/', 'GET', ['start_date' => '2026-11-30', 'end_date' => '2026-11-01']));
    }

    public function test_invalid_requests_are_refused(): void
    {
        $bad = [
            ['period' => 'next_week'],
            ['period' => 'custom'],
            ['start_date' => '2026-11-01'],
            ['end_date' => '2026-11-01'],
            ['start_date' => '2026-02-30', 'end_date' => '2026-03-01'],
            ['start_date' => '01/11/2026', 'end_date' => '30/11/2026'],
            ['start_date' => 'abc', 'end_date' => '2026-11-01'],
            ['start_date' => '2026-11-01 10:00', 'end_date' => '2026-11-02'],
            ['period' => ['this_month']],
            ['start_date' => ['2026-11-01'], 'end_date' => '2026-11-02'],
        ];

        foreach ($bad as $query) {
            try {
                DatePeriod::from(Request::create('/', 'GET', $query), $this->now());
                $this->fail('Requête acceptée à tort : '.json_encode($query));
            } catch (InvalidPeriodException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_every_preset_is_accepted_through_the_request(): void
    {
        foreach (DatePeriod::PRESETS as $key) {
            $this->assertSame($key, DatePeriod::from(Request::create('/', 'GET', ['period' => $key]), $this->now())->key);
        }
    }
}
