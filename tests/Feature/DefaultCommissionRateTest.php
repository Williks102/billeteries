<?php

namespace Tests\Feature;

use App\Components\CommissionCalculator;
use App\Models\CommissionSetting;
use App\Models\Event;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/** Taux de commission par défaut = 6 % (source unique : config('payments.default_commission_rate')). */
class DefaultCommissionRateTest extends TestCase
{
    use BuildsOrderSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        Mail::fake();
        CommissionSetting::query()->delete();
    }

    private function orderOf(int $unitPrice, int $qty): Order
    {
        $order = $this->reservedOrder($this->ticketType(10), $qty);
        $order->orderItems()->update(['unit_price' => $unitPrice, 'total_price' => $unitPrice * $qty]);
        $order->update(['total_amount' => $unitPrice * $qty]);

        return $order->fresh();
    }

    public function test_config_default_is_six_percent(): void
    {
        $this->assertSame(6.0, config('payments.default_commission_rate'));
    }

    public function test_a_without_setting_two_tickets_of_10000_give_1200_commission(): void
    {
        $calc = $this->orderOf(10000, 2)->calculateCommission();

        $this->assertSame([20000, 1200, 18800, 0], [$calc['gross_amount'], $calc['commission_amount'], $calc['net_amount'], $calc['platform_fee']]);
        $this->assertEquals(6, $calc['commission_rate']);
    }

    public function test_b_promoter_then_category_rate_still_override_the_default(): void
    {
        $order = $this->orderOf(10000, 2);
        $event = $order->event;

        CommissionSetting::create(['category_id' => $event->category_id ?? 77, 'commission_rate' => 9, 'is_active' => true, 'valid_from' => now()->subYear()]);
        DB::table('events')->where('id', $event->id)->update(['category_id' => $event->category_id ?? 77]);
        $this->assertSame(1800, $order->fresh()->calculateCommission()['commission_amount'], 'taux catégorie 9 %');

        CommissionSetting::create(['promoter_id' => $event->promoter_id, 'commission_rate' => 15, 'is_active' => true, 'valid_from' => now()->subYear()]);
        $this->assertSame(3000, $order->fresh()->calculateCommission()['commission_amount'], 'taux promoteur 15 % > catégorie > défaut');
    }

    public function test_calculator_component_uses_the_same_default(): void
    {
        $event = new Event(['promoter_id' => 999, 'category_id' => 999]);
        $calc = CommissionCalculator::calculateForEvent($event, 10000, 2);

        $this->assertSame(1200, $calc['commission_amount']);
        $this->assertSame(18800, $calc['net_amount']);
    }

    public function test_e_rounding(): void
    {
        $this->assertSame(60, $this->orderOf(1000, 1)->calculateCommission()['commission_amount']);
        $this->assertSame(60, $this->orderOf(999, 1)->calculateCommission()['commission_amount']); // 59,94 -> 60
        $this->assertSame(0, $this->orderOf(0, 1)->calculateCommission()['commission_amount']);
    }

    public function test_c_pricing_page_shows_six_percent_and_correct_example(): void
    {
        $html = $this->get(route('pages.pricing'))->assertOk()->getContent();

        $this->assertStringContainsString('<strong>6 %</strong>', $html);
        $this->assertMatchesRegularExpression('/id="organizerEarnings">4 700 F</', $html);
        $this->assertStringNotContainsString('4 500', $html);
        $this->assertDoesNotMatchRegularExpression('/(?<![\d.,])(10|8)\s*(%|<small[^>]*>%)/u', $html);
        $this->assertDoesNotMatchRegularExpression('/commission-rate">\s*(10|8)\b/', $html);
    }

    public function test_d_js_receives_the_configured_rate_from_the_views(): void
    {
        $expected = 'data-commission-rate="'.config('payments.default_commission_rate').'"';

        $this->assertStringContainsString($expected, $this->get(route('pages.pricing'))->getContent());
        $this->assertStringContainsString($expected, view('components.promoter-earnings-calculator')->render());

        config(['payments.default_commission_rate' => 7.5]);
        $this->assertStringContainsString('data-commission-rate="7.5"', view('components.promoter-earnings-calculator')->render());
    }

    public function test_f_no_hardcoded_rate_constant_left_in_js(): void
    {
        foreach (['components-promoter-earnings-calculator', 'pages-pricing'] as $name) {
            $js = file_get_contents(public_path("js/$name.js"));
            $this->assertDoesNotMatchRegularExpression('/COMMISSION_RATE\s*=\s*\d/', $js, "$name.js ne doit pas coder le taux en dur");
            $this->assertStringContainsString('dataset.commissionRate', $js);
        }
    }
}
