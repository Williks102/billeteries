<?php

namespace Tests\Feature;

use App\Models\Commission;
use App\Models\CommissionSetting;
use App\Models\Order;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/**
 * Frais de service SUPPRIMÉS : l'acheteur ne paie que le prix des billets ; la plateforme ne perçoit que la
 * commission (taux x sous-total), sans frais fixe.
 */
class NoServiceFeeTest extends TestCase
{
    use BuildsOrderSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        Mail::fake();
        DB::table('events')->insert(['id' => 2, 'promoter_id' => 3, 'title' => 'Festival', 'venue' => 'Parc', 'event_date' => '2027-02-01', 'event_time' => '18:00']);
        CommissionSetting::create([
            'commission_rate' => 10, 'platform_fee_fixed' => 500, 'is_active' => true, 'valid_from' => now()->subYear(),
        ]);
    }

    private function typeFor(int $eventId, int $price, string $name): TicketType
    {
        return TicketType::create([
            'event_id' => $eventId, 'name' => $name, 'price' => $price, 'quantity_available' => 100, 'quantity_sold' => 0,
            'sale_start_date' => now()->subDay(), 'sale_end_date' => now()->addDay(), 'max_per_order' => 10, 'is_active' => true,
        ]);
    }

    /** Panier de 2 événements, 3 lignes, prix de session falsifiés. Sous-totaux serveur : E1 = 2x3333 + 1x5000 = 11 666 ; E2 = 3x2500 = 7 500. */
    private function multiCart(): array
    {
        $a = $this->typeFor(1, 3333, 'Std');
        $b = $this->typeFor(1, 5000, 'VIP');
        $c = $this->typeFor(2, 2500, 'Early');
        $line = fn ($t, $e, $q) => ['ticket_type_id' => $t->id, 'event_id' => $e, 'quantity' => $q, 'ticket_name' => 'x', 'unit_price' => 1, 'total_price' => 1];

        return ['a' => $line($a, 1, 2), 'b' => $line($b, 1, 1), 'c' => $line($c, 2, 3)];
    }

    private function assertOrdersAreSubtotalOnly(): void
    {
        $orders = Order::orderBy('event_id')->get();
        $this->assertCount(2, $orders);
        $this->assertSame(11666, $orders[0]->total_amount);
        $this->assertSame(7500, $orders[1]->total_amount);
        foreach ($orders as $order) {
            $this->assertSame((int) $order->orderItems()->sum('total_price'), $order->total_amount, 'total = somme des lignes, rien d\'autre');
        }
    }

    public function test_authenticated_checkout_total_is_the_sum_of_lines_across_events(): void
    {
        $this->actingAs(User::find(1))->withSession(['cart' => $this->multiCart()])
            ->post(route('checkout.process'), [
                'billing_email' => 'a@b.test', 'billing_phone' => '0102030405', 'terms_accepted' => '1', 'payment_method' => 'bank_transfer',
            ])->assertRedirect();

        $this->assertOrdersAreSubtotalOnly();
    }

    public function test_direct_booking_checkout_total_is_the_sum_of_lines_across_events(): void
    {
        $this->actingAs(User::find(1))->withSession(['direct_booking' => $this->multiCart()])
            ->post(route('checkout.process'), [
                'billing_email' => 'a@b.test', 'billing_phone' => '0102030405', 'terms_accepted' => '1', 'payment_method' => 'cash',
            ])->assertRedirect();

        $this->assertOrdersAreSubtotalOnly();
    }

    public function test_guest_checkout_total_is_the_sum_of_lines_across_events(): void
    {
        $this->withSession(['cart' => $this->multiCart()])
            ->post(route('checkout.guest.process'), [
                'email' => 'invite@example.test', 'first_name' => 'Awa', 'last_name' => 'Kone', 'phone' => '0102030405',
                'terms_accepted' => '1', 'payment_method' => 'mobile_money',
            ])->assertRedirect();

        $this->assertOrdersAreSubtotalOnly();
    }

    public function test_commission_is_rate_times_subtotal_with_no_fixed_fee(): void
    {
        $this->actingAs(User::find(1))->withSession(['cart' => $this->multiCart()])
            ->post(route('checkout.process'), [
                'billing_email' => 'a@b.test', 'billing_phone' => '0102030405', 'terms_accepted' => '1', 'payment_method' => 'bank_transfer',
            ])->assertRedirect();

        // 10 % de 11 666 = 1 166,6 -> 1 167 ; 10 % de 7 500 = 750 (et surtout pas +500 de platform_fee_fixed)
        $expected = [1 => [11666, 1167, 10499], 2 => [7500, 750, 6750]];
        foreach (Order::all() as $order) {
            [$gross, $commission, $net] = $expected[$order->event_id];
            $row = Commission::where('order_id', $order->id)->sole();
            $this->assertSame($gross, $row->gross_amount);
            $this->assertSame($commission, $row->commission_amount);
            $this->assertSame($net, $row->net_amount);
            $this->assertSame($gross, $row->commission_amount + $row->net_amount);
            $this->assertSame(0, $row->platform_fee);
            $this->assertEquals(10, $row->commission_rate);
        }
    }

    public function test_commission_keeps_the_promoter_specific_rate_and_ignores_historical_fee_in_total(): void
    {
        CommissionSetting::create(['promoter_id' => 2, 'commission_rate' => 15, 'is_active' => true, 'valid_from' => now()->subYear()]);
        $type = $this->ticketType(10);
        $order = $this->reservedOrder($type, 2); // 2 lignes x 1000 ; unit_price 0 => on fixe les lignes
        $order->orderItems()->update(['unit_price' => 10000, 'total_price' => 20000]);
        $order->update(['total_amount' => 20500]); // commande historique : 500 de frais dans le total

        $calc = $order->fresh()->calculateCommission();

        $this->assertSame(20000, $calc['gross_amount']);
        $this->assertSame(3000, $calc['commission_amount']); // 15 % (taux promoteur conservé) x 20 000
        $this->assertSame(17000, $calc['net_amount']);
        $this->assertSame(0, $calc['platform_fee']);
    }

    public function test_default_rate_without_any_setting_is_the_configured_default_on_subtotal(): void
    {
        CommissionSetting::query()->delete();
        $type = $this->ticketType(10);
        $order = $this->reservedOrder($type, 2);
        $order->orderItems()->update(['unit_price' => 10000, 'total_price' => 20000]);
        $order->update(['total_amount' => 20000]);

        $calc = $order->fresh()->calculateCommission();

        $this->assertSame([20000, 1200, 18800, 0], [$calc['gross_amount'], $calc['commission_amount'], $calc['net_amount'], $calc['platform_fee']]);
    }

    public function test_no_blade_view_mentions_service_fees(): void
    {
        $offenders = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')));
        foreach ($it as $file) {
            $name = $file->getFilename();
            if (! $file->isFile() || ! str_ends_with($name, '.blade.php') || str_contains($name, '.old')) {
                continue;
            }
            if (preg_match('/frais\s+de\s+service|frais\s+techniques|service_?fee/i', (string) file_get_contents($file->getPathname()))) {
                $offenders[] = $file->getPathname();
            }
        }

        $this->assertSame([], $offenders, 'Les frais de service sont supprimés : aucune vue/email ne doit les mentionner');
    }

    public function test_checkout_pages_show_no_fee_line_and_total_equals_subtotal(): void
    {
        $type = $this->typeFor(1, 4000, 'Std');
        $cart = ['k' => ['ticket_type_id' => $type->id, 'event_id' => 1, 'quantity' => 2, 'ticket_name' => 'Std', 'unit_price' => 4000, 'total_price' => 8000, 'event_title' => 'Concert', 'event_image' => null, 'event_date' => '2027-01-01', 'event_time' => '20:00', 'event_venue' => 'Palais', 'event_slug' => 'concert']];

        foreach ([route('checkout.guest.show')] as $url) {
            $this->withSession(['cart' => $cart])->get($url)->assertOk()
                ->assertDontSee('Frais de service')->assertDontSee('8 500')->assertSee('8,000');
        }
    }
}
