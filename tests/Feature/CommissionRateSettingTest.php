<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\CommissionSetting;
use App\Models\Setting;
use App\Models\User;
use App\Services\CommissionRate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/** Taux de commission par défaut réglable dans l'admin, enregistré en base (secours : DEFAULT_COMMISSION_RATE). */
class CommissionRateSettingTest extends TestCase
{
    use BuildsOrderSchema;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        Mail::fake();
        (include base_path('database/migrations/2026_11_13_100000_create_activity_logs_table.php'))->up();
        (include base_path('database/migrations/2025_07_29_214626_create_settings_table.php'))->up();
        config(['payments.default_commission_rate' => 6]);
        CommissionSetting::query()->delete();
        $this->admin = $this->makeUser(10, 'admin');
    }

    private function save($rate, array $extra = [])
    {
        return $this->actingAs($this->admin)->post(route('admin.settings.store'), ['section' => 'commission', 'commission_rate' => $rate] + $extra);
    }

    private function paidOrder(int $qty = 2): \App\Models\Order
    {
        $order = $this->reservedOrder($this->ticketType(10), $qty);
        $order->orderItems()->update(['unit_price' => 1000, 'total_price' => 1000 * $qty]);

        return $order->fresh();
    }

    public function test_default_is_env_when_nothing_in_database(): void
    {
        $this->assertSame(6.0, CommissionRate::default());
        $this->assertSame('env', CommissionRate::source());
    }

    public function test_save_persists_across_cache_flush_and_requests(): void
    {
        $this->save(8)->assertRedirect(route('admin.settings.index'))->assertSessionHas('success');
        $this->assertSame(8.0, CommissionRate::default());
        Cache::flush();
        $this->assertSame(8.0, CommissionRate::default());
        $row = DB::table('settings')->where('key', 'commission.default_rate')->first();
        $this->assertSame('payment', $row->group);
        $this->assertSame('decimal', $row->type);
        // Requête suivante : l'écran affiche 8 et la source « administration »
        $this->get(route('admin.settings.index'))->assertSee('défini dans l\'administration', false)->assertSee('8 %');
    }

    public function test_invalid_stored_values_fall_back_to_env(): void
    {
        foreach (['abc', '150', '-1', ''] as $bad) {
            DB::table('settings')->updateOrInsert(['key' => 'commission.default_rate'], ['value' => $bad, 'type' => 'decimal', 'group' => 'payment']);
            $this->assertSame(6.0, CommissionRate::default(), "valeur « $bad »");
            $this->assertSame('env', CommissionRate::source());
        }
    }

    public function test_zero_is_valid_and_applied(): void
    {
        $this->save(0)->assertSessionHas('success');
        $this->assertSame(0.0, CommissionRate::default());
        $this->assertSame('database', CommissionRate::source());
        $calc = $this->paidOrder()->calculateCommission();
        $this->assertSame(0, (int) $calc['commission_amount']);
        $this->assertSame(2000, (int) $calc['net_amount']);
    }

    public function test_new_confirmed_order_uses_new_rate_but_existing_commissions_are_untouched(): void
    {
        $first = $this->paidOrder();
        $first->markAsPaid('REF1');
        $before = DB::table('commissions')->where('order_id', $first->id)->first();
        $this->assertEquals(6, $before->commission_rate);
        $this->assertSame(120, (int) $before->commission_amount);

        $this->save(10)->assertSessionHas('success');

        $unchanged = DB::table('commissions')->where('order_id', $first->id)->first();
        $this->assertEquals(6, $unchanged->commission_rate);
        $this->assertSame(120, (int) $unchanged->commission_amount);

        $second = $this->paidOrder();
        $second->markAsPaid('REF2');
        $new = DB::table('commissions')->where('order_id', $second->id)->first();
        $this->assertEquals(10, $new->commission_rate);
        $this->assertSame(200, (int) $new->commission_amount);
    }

    public function test_promoter_or_category_setting_still_wins(): void
    {
        $this->save(10);
        $base = ['is_active' => true, 'valid_from' => now()->subDay()->toDateString(), 'created_at' => now(), 'updated_at' => now()];
        DB::table('commission_settings')->insert(['category_id' => 5, 'commission_rate' => 4] + $base);
        DB::table('events')->where('id', 1)->update(['category_id' => 5]);
        $this->assertEquals(4, $this->paidOrder()->calculateCommission()['commission_rate']);
        DB::table('commission_settings')->insert(['promoter_id' => 2, 'commission_rate' => 3] + $base);
        $this->assertEquals(3, $this->paidOrder()->calculateCommission()['commission_rate']);
    }

    public function test_reset_returns_to_server_default_and_is_logged(): void
    {
        $this->save(8);
        $this->save(0, ['reset' => 1])->assertSessionHas('success');
        $this->assertSame(0, DB::table('settings')->where('key', 'commission.default_rate')->count());
        $this->assertSame(6.0, CommissionRate::default());
        $logs = ActivityLog::where('action', 'settings.updated')->orderBy('id')->get();
        $this->assertCount(2, $logs);
        $this->assertEquals(['from' => 8, 'to' => 6], $logs[1]->properties['commission_rate']);
        // Reset sans valeur en base : rien de plus
        $this->save(0, ['reset' => 1]);
        $this->assertSame(2, ActivityLog::where('action', 'settings.updated')->count());
    }

    public function test_log_has_effective_before_and_after_and_no_line_when_unchanged(): void
    {
        $this->save(8);
        $log = ActivityLog::where('action', 'settings.updated')->sole();
        $this->assertEquals(['from' => 6, 'to' => 8], $log->properties['commission_rate']);
        $this->save(8);
        $this->assertSame(1, ActivityLog::where('action', 'settings.updated')->count());
        $this->save(9);
        $last = ActivityLog::where('action', 'settings.updated')->latest('id')->first();
        $this->assertEquals(['from' => 8, 'to' => 9], $last->properties['commission_rate']);
    }

    public function test_validation_rejects_bad_values_with_french_messages(): void
    {
        foreach (['-1' => 'négatif', '101' => 'dépasser 100', 'abc' => 'un nombre', '5.123' => '2 décimales', '' => 'Indiquez'] as $bad => $msg) {
            $res = $this->save((string) $bad);
            $res->assertSessionHasErrors('commission_rate');
            $this->assertStringContainsString($msg, session('errors')->first('commission_rate'));
            $this->assertSame('env', CommissionRate::source());
        }
        $this->save('7.25')->assertSessionHasNoErrors();
        $this->assertSame(7.25, CommissionRate::default());
    }

    public function test_general_form_also_persists_the_rate(): void
    {
        $this->actingAs($this->admin)->post(route('admin.settings.store'), [
            'section' => 'general', 'app_name' => 'B', 'app_url' => 'https://example.test', 'timezone' => 'Africa/Abidjan',
            'commission_rate' => 8, 'max_tickets_per_order' => 10,
        ])->assertSessionHas('success');
        $this->assertSame(8.0, CommissionRate::default());
    }

    public function test_access_is_admin_only(): void
    {
        $this->makeUser(2, 'promoteur');
        foreach ([[1, 'acheteur'], [2, 'promoteur']] as [$id]) {
            $this->actingAs(User::find($id))->post(route('admin.settings.store'), ['section' => 'commission', 'commission_rate' => 1])
                ->assertStatus(403);
        }
        auth()->logout();
        $this->post(route('admin.settings.store'), ['section' => 'commission', 'commission_rate' => 1])->assertRedirect();
        $this->assertSame('env', CommissionRate::source());
        $this->actingAs($this->admin)->get(route('admin.settings.index'))->assertOk();
    }

    public function test_public_pages_and_calculator_reflect_database_value(): void
    {
        $this->save(7.5);
        $this->assertStringContainsString('7,5 %', $this->get(route('pages.promoter-guide'))->getContent());
        $this->assertStringContainsString('data-commission-rate="7.5"', $this->get(route('pages.pricing'))->getContent());
        $this->assertStringContainsString('data-commission-rate="7.5"', view('components.promoter-earnings-calculator')->render());
    }

    public function test_missing_settings_table_does_not_break_anything(): void
    {
        Schema::drop('settings');
        $this->assertSame(6.0, CommissionRate::default());
        $this->assertSame('env', CommissionRate::source());
        $this->assertNull(CommissionRate::stored());
        $this->assertSame(120, (int) $this->paidOrder()->calculateCommission()['commission_amount']);
    }

    public function test_no_other_code_reads_the_env_default_directly(): void
    {
        $hits = [];
        foreach (['app', 'resources/views'] as $dir) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path($dir)));
            foreach ($it as $f) {
                if ($f->isFile() && str_contains((string) file_get_contents($f->getPathname()), "config('payments.default_commission_rate')")
                    && ! str_ends_with($f->getPathname(), 'Services/CommissionRate.php')) {
                    $hits[] = $f->getPathname();
                }
            }
        }
        $this->assertSame([], $hits);
    }
}
