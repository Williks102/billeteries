<?php

namespace Tests\Feature;

use App\Models\Order;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/**
 * P4 : stock atomique, libération idempotente, commande planifiée ; C1 : preuve de session du guest_token.
 */
class StockAndOrderFlowTest extends TestCase
{
    use BuildsOrderSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
    }

    public function test_reserve_never_oversells_and_increments_once(): void
    {
        $type = $this->ticketType(available: 1);

        $this->assertTrue($type->fresh()->reserveTickets(1));
        $this->assertFalse($type->fresh()->reserveTickets(1));
        $this->assertSame(1, $type->fresh()->quantity_sold);

        $other = $this->ticketType(available: 10);
        $this->assertTrue($other->reserveTickets(3));
        $this->assertSame(3, $other->fresh()->quantity_sold);
    }

    public function test_release_stock_is_idempotent(): void
    {
        $type = $this->ticketType(5);
        $order = $this->reservedOrder($type, 2);

        $this->assertTrue($order->releaseStock());
        $this->assertFalse($order->fresh()->releaseStock());
        $this->assertSame(0, $type->fresh()->quantity_sold);
    }

    public function test_release_expired_command_frees_only_expired_unpaid_orders(): void
    {
        $type = $this->ticketType(10);
        $expired = $this->reservedOrder($type, 2, 'pending', now()->subMinute());
        $fresh = $this->reservedOrder($type, 1, 'processing', now()->addMinutes(10));
        $paid = $this->reservedOrder($type, 1, 'paid', now()->subMinute());

        $this->artisan('orders:release-expired')->assertSuccessful();

        $this->assertSame('failed', $expired->fresh()->payment_status);
        $this->assertSame('processing', $fresh->fresh()->payment_status);
        $this->assertSame('paid', $paid->fresh()->payment_status);
        $this->assertSame(2, $type->fresh()->quantity_sold);

        $this->artisan('orders:release-expired')->assertSuccessful();
        $this->assertSame(2, $type->fresh()->quantity_sold);
    }

    /** Plus aucune route de paiement en ligne (fournisseur retiré) : paiement manuel uniquement. */
    public function test_no_online_payment_provider_routes_remain(): void
    {
        $uris = collect(app('router')->getRoutes())->map->uri()->filter(fn ($u) => str_starts_with($u, 'payments/'));
        $this->assertCount(0, $uris);

        $this->post('/payments/notification', [])->assertNotFound();
    }
}
