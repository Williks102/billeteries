<?php

namespace Tests\Feature;

use App\Mail\PaymentConfirmation;
use App\Models\Commission;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Ticket;
use App\Services\CheckoutService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/** Chemins admin updateStatus / update / bulkAction : « paid » passe par le service ; failed/refunded = dette connue. */
class AdminOrderStatusPathsTest extends TestCase
{
    use BuildsOrderSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        Mail::fake();
    }

    private function realOrder($type, int $qty = 1): Order
    {
        $service = app(CheckoutService::class);
        $groups = $service->buildEventGroups([['ticket_type_id' => $type->id, 'quantity' => $qty]]);

        return DB::transaction(fn () => $service->createOrder(1, 1, $groups[1], ['payment_method' => 'cash']));
    }

    private function assertPaidOnce(Order $order, int $tickets = 1): void
    {
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame(1, Commission::where('order_id', $order->id)->count());
        $this->assertSame(1, Payment::where('order_id', $order->id)->count());
        $this->assertSame($tickets, Ticket::where('status', 'sold')->count());
        Mail::assertSent(PaymentConfirmation::class, 1);
    }

    public function test_update_status_to_paid_goes_through_service_once(): void
    {
        $order = $this->realOrder($this->ticketType(5));
        $admin = $this->makeUser(10, 'admin');

        foreach ([1, 2] as $_) {
            $this->actingAs($admin)->patchJson(route('admin.orders.update-status', $order), ['payment_status' => 'paid'])->assertOk();
        }

        $this->assertPaidOnce($order);
    }

    public function test_update_to_paid_goes_through_service_once(): void
    {
        $order = $this->realOrder($this->ticketType(5));
        $admin = $this->makeUser(10, 'admin');

        foreach ([1, 2] as $_) {
            $this->actingAs($admin)->put(route('admin.orders.update', $order), ['payment_status' => 'paid'])
                ->assertRedirect(route('admin.orders.show', $order));
        }

        $this->assertPaidOnce($order);
    }

    public function test_bulk_mark_paid_goes_through_service_once_and_is_idempotent(): void
    {
        $type = $this->ticketType(5);
        $a = $this->realOrder($type);
        $b = $this->realOrder($type);
        $admin = $this->makeUser(10, 'admin');

        foreach ([1, 2] as $_) {
            $this->actingAs($admin)->post(route('admin.orders.bulk-action'), ['action' => 'mark_paid', 'orders' => [$a->id, $b->id]])
                ->assertRedirect();
        }

        $this->assertSame(2, Payment::count());
        $this->assertSame(2, Commission::count());
        $this->assertSame(2, Ticket::where('status', 'sold')->count());
        $this->assertSame(2, $type->fresh()->quantity_sold);
        Mail::assertSent(PaymentConfirmation::class, 2);
    }

    public function test_update_status_paid_on_refunded_order_is_refused(): void
    {
        $order = $this->reservedOrder($this->ticketType(5), 1, 'refunded');

        $this->actingAs($this->makeUser(10, 'admin'))
            ->patchJson(route('admin.orders.update-status', $order), ['payment_status' => 'paid'])
            ->assertStatus(422);

        $this->assertSame('refunded', $order->fresh()->payment_status);
        $this->assertSame(0, Payment::count());
    }

    /**
     * DETTE CONNUE (documente le comportement ACTUEL, pas le comportement souhaité) :
     * passer une commande pending à failed/refunded via updateStatus annule les billets mais ne
     * libère PAS le stock (quantity_sold reste compté). Seul Order::releaseStock() libère.
     * Quand l'admin sera corrigé (agent paiements-stock), inverser les assertions marquées DETTE.
     */
    public function test_known_debt_update_status_failed_or_refunded_cancels_tickets_but_keeps_stock(): void
    {
        $type = $this->ticketType(5);
        $admin = $this->makeUser(10, 'admin');

        foreach (['failed', 'refunded'] as $status) {
            $order = $this->realOrder($type, 2);
            $before = $type->fresh()->quantity_sold;

            $this->actingAs($admin)->patchJson(route('admin.orders.update-status', $order), ['payment_status' => $status])->assertOk();

            $this->assertSame($status, $order->fresh()->payment_status);
            $this->assertSame(2, $order->tickets()->where('status', 'cancelled')->count());
            $this->assertSame($before, $type->fresh()->quantity_sold, 'DETTE : stock non libéré (' . $status . ')');
        }
    }

    /** DETTE CONNUE : idem pour update() (formulaire d'édition) et bulkAction mark_failed. */
    public function test_known_debt_update_and_bulk_failed_keep_stock_reserved(): void
    {
        $type = $this->ticketType(5);
        $admin = $this->makeUser(10, 'admin');
        $viaUpdate = $this->realOrder($type);
        $viaBulk = $this->realOrder($type);

        $this->actingAs($admin)->put(route('admin.orders.update', $viaUpdate), ['payment_status' => 'failed'])->assertRedirect();
        $this->actingAs($admin)->post(route('admin.orders.bulk-action'), ['action' => 'mark_failed', 'orders' => [$viaBulk->id]])->assertRedirect();

        $this->assertSame('failed', $viaUpdate->fresh()->payment_status);
        $this->assertSame('failed', $viaBulk->fresh()->payment_status);
        $this->assertSame(2, $type->fresh()->quantity_sold, 'DETTE : stock non libéré');
    }
}
