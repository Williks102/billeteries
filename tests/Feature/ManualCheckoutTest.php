<?php

namespace Tests\Feature;

use App\Mail\ManualPaymentInstructions;
use App\Models\Commission;
use App\Models\Order;
use App\Models\Ticket;
use App\Services\CheckoutService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/** Checkout en paiement manuel : commande pending à 48 h, méthodes validées, preuve de session. */
class ManualCheckoutTest extends TestCase
{
    use BuildsOrderSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        Mail::fake();
    }

    private function cartFor($type, int $qty): array
    {
        return ['k' => [
            'ticket_type_id' => $type->id, 'event_id' => 1, 'quantity' => $qty,
            'ticket_name' => 'Std', 'unit_price' => 1, 'total_price' => 1, // falsifiés : ignorés
        ]];
    }

    private function guestPayload(array $override = []): array
    {
        return array_merge([
            'email' => 'invite@example.test', 'first_name' => 'Awa', 'last_name' => 'Kone',
            'phone' => '0102030405', 'terms_accepted' => '1', 'payment_method' => 'bank_transfer',
        ], $override);
    }

    public function test_service_creates_pending_order_expiring_in_48h_and_one_commission(): void
    {
        $type = $this->ticketType(10);
        $service = app(CheckoutService::class);
        $groups = $service->buildEventGroups($this->cartFor($type, 2));

        $order = DB::transaction(fn () => $service->createOrder(1, 1, $groups[1], ['payment_method' => 'mobile_money']));

        $this->assertSame('pending', $order->payment_status);
        $this->assertSame(2000, $order->total_amount);
        $this->assertEqualsWithDelta(
            now()->addMinutes(2880)->timestamp, $order->expires_at->timestamp, 5
        );
        $this->assertSame(2, $type->fresh()->quantity_sold);
        $this->assertSame(2, Ticket::where('status', 'reserved')->count());
        $this->assertSame(1, Commission::where('order_id', $order->id)->count());
    }

    public function test_manual_reservation_minutes_is_configurable(): void
    {
        config(['payments.manual_reservation_minutes' => 60]);
        $type = $this->ticketType(10);
        $service = app(CheckoutService::class);
        $groups = $service->buildEventGroups($this->cartFor($type, 1));

        $order = DB::transaction(fn () => $service->createOrder(1, 1, $groups[1], ['payment_method' => 'cash']));

        $this->assertEqualsWithDelta(now()->addMinutes(60)->timestamp, $order->expires_at->timestamp, 5);
    }

    public function test_guest_checkout_rejects_unknown_payment_method(): void
    {
        $type = $this->ticketType(10);

        foreach (['paiementpro', 'card', ''] as $method) {
            $this->withSession(['cart' => $this->cartFor($type, 1)])
                ->post(route('checkout.guest.process'), $this->guestPayload(['payment_method' => $method]))
                ->assertSessionHasErrors('payment_method');
        }

        $this->assertSame(0, Order::count());
        $this->assertSame(0, $type->fresh()->quantity_sold);
    }

    public function test_authenticated_checkout_rejects_unknown_payment_method(): void
    {
        $type = $this->ticketType(10);

        $this->actingAs(\App\Models\User::find(1))
            ->withSession(['cart' => $this->cartFor($type, 1)])
            ->post(route('checkout.process'), [
                'billing_email' => 'a@b.test', 'billing_phone' => '0102030405',
                'terms_accepted' => '1', 'payment_method' => 'paiementpro',
            ])->assertSessionHasErrors('payment_method');

        $this->assertSame(0, Order::count());
    }

    public function test_guest_checkout_creates_pending_order_and_redirects_to_confirmation_with_session_proof(): void
    {
        $type = $this->ticketType(10);

        $response = $this->withSession(['cart' => $this->cartFor($type, 2)])
            ->post(route('checkout.guest.process'), $this->guestPayload(['payment_method' => 'mobile_money']));

        $order = Order::sole();
        $response->assertRedirect(route('checkout.guest.confirmation', $order->guest_token));
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame('mobile_money', $order->payment_method);
        $this->assertSame(2000, $order->total_amount); // prix serveur, pas ceux de la session
        $this->assertEqualsWithDelta(now()->addMinutes(1440)->timestamp, $order->expires_at->timestamp, 5); // invité : 24 h (E2)
        $this->assertSame($order->guest_token, session('guest_order_tokens.' . $order->id)); // C1
        $this->assertNull(session('cart'));
        Mail::assertQueued(ManualPaymentInstructions::class, 1);

        // Page de confirmation : numéro, montant, instructions, date limite
        $page = $this->get(route('checkout.guest.confirmation', $order->guest_token));
        $page->assertOk()
            ->assertSee($order->order_number)
            ->assertSee(\App\Helpers\CurrencyHelper::formatNumber($order->total_amount))
            ->assertSee('Mobile Money')
            ->assertSee($order->expires_at->format('d/m/Y à H:i'));

    }

    public function test_authenticated_checkout_redirects_to_confirmation(): void
    {
        $type = $this->ticketType(10);

        $response = $this->actingAs(\App\Models\User::find(1))
            ->withSession(['cart' => $this->cartFor($type, 1)])
            ->post(route('checkout.process'), [
                'billing_email' => 'a@b.test', 'billing_phone' => '0102030405',
                'terms_accepted' => '1', 'payment_method' => 'cash',
            ]);

        $order = Order::sole();
        $response->assertRedirect(route('checkout.confirmation', $order));
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame('cash', $order->payment_method);
        Mail::assertQueued(ManualPaymentInstructions::class, 1);
    }

    public function test_instructions_come_from_config_without_hardcoded_account(): void
    {
        config(['payments.manual_methods.bank_transfer.details.IBAN / RIB' => 'TEST-IBAN-0000']);

        $info = CheckoutService::manualPaymentInfo('bank_transfer');
        $this->assertSame('TEST-IBAN-0000', $info['details']['IBAN / RIB']);

        config(['payments.manual_methods.bank_transfer.details.IBAN / RIB' => null]);
        $this->assertStringContainsString('communiqué', CheckoutService::manualPaymentInfo('bank_transfer')['details']['IBAN / RIB']);
        $this->assertSame(['bank_transfer', 'mobile_money', 'cash'], CheckoutService::manualMethodKeys());
    }

    public function test_checkout_pages_render_manual_methods_without_provider_channels(): void
    {
        $type = $this->ticketType(10);
        $cart = ['k' => [
            'ticket_type_id' => $type->id, 'event_id' => 1, 'quantity' => 1, 'ticket_name' => 'Std',
            'unit_price' => 1000, 'total_price' => 1000, 'event_title' => 'Concert',
            'event_date' => '01/01/2027', 'event_venue' => 'Palais', 'event_image' => null,
        ]];

        $guest = $this->withSession(['cart' => $cart])->get(route('checkout.guest.show'));
        $guest->assertOk()->assertSee('Virement bancaire')->assertSee('Mobile Money')->assertSee('Espèces')
            ->assertDontSee('name="channel"', false);

        $auth = $this->actingAs(\App\Models\User::find(1))->withSession(['cart' => $cart])->get(route('checkout.show'));
        $auth->assertOk()->assertSee('Virement bancaire')->assertSee('Espèces')->assertDontSee('name="channel"', false);
    }

    public function test_instructions_email_renders_amount_reference_and_deadline(): void
    {
        $order = $this->reservedOrder($this->ticketType(5), 1, 'pending', now()->addDays(2))->fresh();

        $html = (new ManualPaymentInstructions($order))->render();

        $this->assertStringContainsString($order->order_number, $html);
        $this->assertStringContainsString('Virement bancaire', $html);
        $this->assertStringContainsString($order->expires_at->format('d/m/Y à H:i'), $html);
    }
}
