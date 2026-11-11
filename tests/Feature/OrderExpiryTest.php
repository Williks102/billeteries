<?php

namespace Tests\Feature;

use App\Models\Commission;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Ticket;
use App\Services\CheckoutService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/** Expiration des commandes manuelles et confirmation tardive par l'admin. */
class OrderExpiryTest extends TestCase
{
    use BuildsOrderSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        Mail::fake();
    }

    /** Commande créée par le vrai CheckoutService (billets reserved, commission, expires_at). */
    private function realOrder($type, int $qty): Order
    {
        $service = app(CheckoutService::class);
        $groups = $service->buildEventGroups([['ticket_type_id' => $type->id, 'quantity' => $qty]]);

        return DB::transaction(fn () => $service->createOrder(1, 1, $groups[1], ['payment_method' => 'bank_transfer']));
    }

    public function test_command_fails_expired_pending_order_frees_stock_and_cancels_tickets(): void
    {
        $type = $this->ticketType(10);
        $expired = $this->realOrder($type, 3);
        $expired->update(['expires_at' => now()->subMinute()]);
        $this->assertSame(3, $type->fresh()->quantity_sold);

        $this->artisan('orders:release-expired')->assertSuccessful();

        $this->assertSame('failed', $expired->fresh()->payment_status);
        $this->assertSame(0, $type->fresh()->quantity_sold);
        $this->assertSame(3, Ticket::where('status', 'cancelled')->count());
        $this->assertSame(0, Ticket::where('status', 'reserved')->count());
    }

    public function test_command_never_touches_paid_or_unexpired_orders(): void
    {
        $type = $this->ticketType(10);
        $paid = $this->realOrder($type, 1);
        $paid->update(['payment_status' => 'paid', 'expires_at' => now()->subDay()]); // même avec expires_at passé
        $running = $this->realOrder($type, 1); // expire dans 48 h

        $this->artisan('orders:release-expired')->assertSuccessful();

        $this->assertSame('paid', $paid->fresh()->payment_status);
        $this->assertSame('pending', $running->fresh()->payment_status);
        $this->assertSame(2, $type->fresh()->quantity_sold);
        $this->assertSame(2, Ticket::where('status', 'reserved')->count());
    }

    public function test_admin_confirmation_of_expired_order_whose_stock_was_taken_is_refused(): void
    {
        $type = $this->ticketType(1);
        $order = $this->realOrder($type, 1);
        $order->update(['expires_at' => now()->subMinute()]);
        $this->artisan('orders:release-expired')->assertSuccessful();
        $this->assertTrue($type->fresh()->reserveTickets(1)); // dernier billet repris par un autre acheteur

        $this->actingAs($this->makeUser(10, 'admin'))
            ->postJson(route('admin.orders.confirm-payment', $order))
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonFragment(['message' => 'Stock indisponible : la commande avait expiré et les billets ne sont plus disponibles. Remboursement ou relance manuelle de l\'acheteur nécessaire.']);

        $this->assertSame('failed', $order->fresh()->payment_status);
        $this->assertNull($order->fresh()->paid_at);
        $this->assertSame(0, Payment::count());
        $this->assertSame(0, Ticket::where('status', 'sold')->count());
        $this->assertSame(1, $type->fresh()->quantity_sold); // pas de survente
        $this->assertSame(1, Commission::where('order_id', $order->id)->count());
    }

    public function test_admin_confirmation_of_expired_order_with_free_stock_reclaims_and_pays(): void
    {
        $type = $this->ticketType(5);
        $order = $this->realOrder($type, 2);
        $order->update(['expires_at' => now()->subMinute()]);
        $this->artisan('orders:release-expired')->assertSuccessful();
        $this->assertSame(0, $type->fresh()->quantity_sold);

        $this->actingAs($this->makeUser(10, 'admin'))
            ->postJson(route('admin.orders.confirm-payment', $order), ['payment_reference' => 'LATE-1'])
            ->assertOk()->assertJsonPath('success', true);

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertNull($order->expires_at);
        $this->assertSame(2, Ticket::where('status', 'sold')->count());
        $this->assertSame(2, $type->fresh()->quantity_sold);
        $this->assertSame(1, Payment::count());
        $this->assertSame(1, Commission::where('order_id', $order->id)->count());
    }
}
