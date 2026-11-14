<?php

namespace Tests\Feature;

use App\Mail\ManualPaymentInstructions;
use App\Mail\PaymentConfirmation;
use App\Models\Commission;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\OrderPaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/** Parcours de bout en bout du paiement manuel (connecté, invité) et double confirmation. */
class ManualPaymentJourneyTest extends TestCase
{
    use BuildsOrderSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        Mail::fake();
    }

    private function buyerPayload(array $o = []): array
    {
        return $o + [
            'billing_email' => 'acheteur@example.test', 'billing_phone' => '0102030405',
            'terms_accepted' => '1', 'payment_method' => 'bank_transfer',
        ];
    }

    private function guestPayload(array $o = []): array
    {
        return $o + [
            'email' => 'invite@example.test', 'first_name' => 'Awa', 'last_name' => 'Kone',
            'phone' => '0102030405', 'terms_accepted' => '1', 'payment_method' => 'mobile_money',
        ];
    }

    public function test_authenticated_journey_checkout_then_admin_confirmation(): void
    {
        $type = $this->ticketType(10);
        $admin = $this->makeUser(10, 'admin');

        // 1. Checkout connecté, prix de session falsifiés
        $this->actingAs(User::find(1))
            ->withSession(['cart' => $this->tamperedCart($type, 2)])
            ->post(route('checkout.process'), $this->buyerPayload())
            ->assertRedirect();

        $order = Order::sole();
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame(2000, $order->total_amount); // item 7
        $this->assertEqualsWithDelta(now()->addHours(48)->timestamp, $order->expires_at->timestamp, 10);
        $this->assertSame(2, Ticket::where('status', 'reserved')->count());
        $this->assertSame(2, $type->fresh()->quantity_sold);
        $this->assertSame(1, Commission::where('order_id', $order->id)->count());
        Mail::assertQueued(ManualPaymentInstructions::class, 1);
        Mail::assertNotQueued(PaymentConfirmation::class);

        // 2. L'admin confirme
        $this->actingAs($admin)
            ->post(route('admin.orders.confirm-payment', $order), ['payment_reference' => 'VIR-1'])
            ->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertNull($order->expires_at);
        $this->assertSame(2, Ticket::where('status', 'sold')->count());
        $this->assertSame(0, Ticket::where('status', 'reserved')->count());
        $this->assertSame(2, $type->fresh()->quantity_sold); // pas de double comptage
        $this->assertSame(1, Commission::where('order_id', $order->id)->count());
        $this->assertSame(1, Payment::where('order_id', $order->id)->count());
        Mail::assertQueued(PaymentConfirmation::class, 1);
    }

    public function test_guest_journey_session_proof_and_other_visitor_is_locked_out(): void
    {
        $type = $this->ticketType(10);

        $this->withSession(['cart' => $this->tamperedCart($type, 1)])
            ->post(route('checkout.guest.process'), $this->guestPayload())
            ->assertRedirect();

        $order = Order::sole();
        $this->assertNotEmpty($order->guest_token);
        $this->assertSame($order->guest_token, session('guest_order_tokens.' . $order->id));
        $this->assertSame(1000, $order->total_amount);
        $this->get(route('checkout.guest.confirmation', $order->guest_token))
            ->assertOk()->assertSee($order->order_number);

        // Autre visiteur : nouvelle session, aucune preuve
        $this->flushSession();
        $this->assertNull(session('guest_order_tokens.' . $order->id));

        // L'identifiant de commande n'est pas un token : 404, pas de fuite
        $this->get(route('checkout.guest.confirmation', (string) $order->id))->assertNotFound();
        $this->get(route('checkout.guest.confirmation', 'inconnu'))->assertNotFound();

        // Page authentifiée par id : exige une connexion, et un autre acheteur est refusé
        $this->get(route('checkout.confirmation', $order))->assertRedirect();
        $this->actingAs($this->makeUser(30, 'acheteur'))
            ->get(route('checkout.confirmation', $order))->assertForbidden();

        // Aucune route publique d'annulation de commande n'existe (l'annulation n'est pas exposée aux invités)
        foreach (['POST', 'PATCH', 'DELETE'] as $verb) {
            foreach (['/checkout/cancel/' . $order->id, '/orders/' . $order->id . '/cancel', '/checkout/guest/cancel/' . $order->guest_token] as $url) {
                $status = $this->call($verb, $url)->getStatusCode();
                $this->assertContains($status, [302, 403, 404, 405, 419], "$verb $url a répondu $status");
            }
        }
        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertSame(1, $type->fresh()->quantity_sold);
    }

    public function test_no_public_route_cancels_an_order(): void
    {
        $cancelRoutes = collect(app('router')->getRoutes())
            ->filter(fn ($r) => str_contains($r->uri(), 'cancel') && !str_starts_with($r->uri(), 'admin/') && !str_starts_with($r->uri(), 'promoteur/'))
            ->map->uri()->values()->all();

        $this->assertSame([], $cancelRoutes, 'Une route d\'annulation hors admin/promoteur est apparue : tester son autorisation.');
    }

    public function test_two_successive_confirmations_with_stale_instances_yield_one_payment(): void
    {
        $type = $this->ticketType(10);
        $groups = app(CheckoutService::class)->buildEventGroups([['ticket_type_id' => $type->id, 'quantity' => 2]]);
        $created = DB::transaction(fn () => app(CheckoutService::class)->createOrder(1, 1, $groups[1], ['payment_method' => 'cash']));

        // Deux administrateurs ont chargé la commande « pending » avant l'une ou l'autre confirmation
        $first = Order::find($created->id);
        $second = Order::find($created->id);
        $this->assertSame('pending', $second->payment_status);

        $admin = $this->makeUser(10, 'admin');
        app(OrderPaymentService::class)->confirmPayment($first, 'A', $admin);
        app(OrderPaymentService::class)->confirmPayment($second, 'B', $admin); // instance périmée

        $order = Order::find($created->id);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('A', $order->payment_reference);
        $this->assertSame(1, Payment::where('order_id', $order->id)->count());
        $this->assertSame(1, Commission::where('order_id', $order->id)->count());
        $this->assertSame(2, Ticket::where('status', 'sold')->count());
        $this->assertSame(2, $type->fresh()->quantity_sold);
        Mail::assertQueued(PaymentConfirmation::class, 1);
    }
}
