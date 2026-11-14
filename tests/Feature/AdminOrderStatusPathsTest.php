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

/** Chemins admin updateStatus / update / bulkAction / refund : tous passent par OrderPaymentService::changeStatus. */
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
        Mail::assertQueued(PaymentConfirmation::class, 1);
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
        Mail::assertQueued(PaymentConfirmation::class, 2);
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

    /** M1 : failed/refunded depuis l'admin libèrent le stock (jamais de quantity_sold incohérent). */
    public function test_update_status_failed_releases_stock_and_cancels_tickets(): void
    {
        $type = $this->ticketType(5);
        $admin = $this->makeUser(10, 'admin');
        $order = $this->realOrder($type, 2);
        $this->assertSame(2, $type->fresh()->quantity_sold);

        $this->actingAs($admin)->patchJson(route('admin.orders.update-status', $order), ['payment_status' => 'failed'])->assertOk();

        $this->assertSame('failed', $order->fresh()->payment_status);
        $this->assertSame(2, $order->tickets()->where('status', 'cancelled')->count());
        $this->assertSame(0, $type->fresh()->quantity_sold);
        $this->assertSame('cancelled', $order->commission()->first()->status);
    }

    public function test_refund_of_paid_order_releases_stock_once(): void
    {
        $type = $this->ticketType(5);
        $admin = $this->makeUser(10, 'admin');
        $order = $this->realOrder($type, 2);
        app(\App\Services\OrderPaymentService::class)->confirmPayment($order);
        $this->assertSame(2, $type->fresh()->quantity_sold);

        $this->actingAs($admin)->post(route('admin.orders.refund', $order), ['reason' => 'Doublon'])->assertRedirect(route('admin.orders.show', $order));
        $this->assertSame('refunded', $order->fresh()->payment_status);
        $this->assertSame('Doublon', $order->fresh()->admin_notes);
        $this->assertSame(0, $type->fresh()->quantity_sold);
        $this->assertSame(2, $order->tickets()->where('status', 'cancelled')->count());
        $this->assertSame('held', $order->commission()->first()->status);

        // Un second remboursement est refusé et ne libère rien de plus
        $this->actingAs($admin)->post(route('admin.orders.refund', $order))->assertSessionHas('error');
        $this->assertSame(0, $type->fresh()->quantity_sold);
    }

    /** M1 : update() (formulaire) et bulkAction mark_failed libèrent aussi le stock ; admin_notes est enregistré (F3). */
    public function test_update_and_bulk_failed_release_stock_and_update_keeps_notes(): void
    {
        $type = $this->ticketType(5);
        $admin = $this->makeUser(10, 'admin');
        $viaUpdate = $this->realOrder($type);
        $viaBulk = $this->realOrder($type);

        $this->actingAs($admin)->put(route('admin.orders.update', $viaUpdate), ['payment_status' => 'failed', 'admin_notes' => 'Client injoignable'])->assertRedirect();
        $this->actingAs($admin)->post(route('admin.orders.bulk-action'), ['action' => 'mark_failed', 'orders' => [$viaBulk->id]])->assertRedirect();

        $this->assertSame('failed', $viaUpdate->fresh()->payment_status);
        $this->assertSame('Client injoignable', $viaUpdate->fresh()->admin_notes);
        $this->assertSame('failed', $viaBulk->fresh()->payment_status);
        $this->assertSame(0, $type->fresh()->quantity_sold);
    }

    public function test_update_paid_keeps_admin_notes(): void
    {
        $order = $this->realOrder($this->ticketType(5));

        $this->actingAs($this->makeUser(10, 'admin'))
            ->put(route('admin.orders.update', $order), ['payment_status' => 'paid', 'admin_notes' => 'Reçu en espèces'])->assertRedirect();

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('Reçu en espèces', $order->fresh()->admin_notes);
    }

    /** F3 : le mark_paid en lot remonte l'échec par commande au lieu de l'avaler. */
    public function test_bulk_mark_paid_reports_failure_per_order(): void
    {
        $order = $this->realOrder($this->ticketType(5));
        $this->mock(\App\Services\OrderPaymentService::class)
            ->shouldReceive('changeStatus')->once()
            ->andThrow(new \App\Exceptions\OrderPaymentException('Stock indisponible'));

        $this->actingAs($this->makeUser(10, 'admin'))
            ->post(route('admin.orders.bulk-action'), ['action' => 'mark_paid', 'orders' => [$order->id]])
            ->assertSessionHas('error', fn ($m) => str_contains($m, $order->order_number) && str_contains($m, 'Stock indisponible'))
            ->assertSessionHas('success', fn ($m) => str_starts_with($m, '0 commande'));
    }

    /** Ex-test connu rouge (KnownBugsTest) : failed par l'admin puis paid ne double plus le stock (M1). */
    public function test_failed_by_admin_then_paid_must_not_double_count_stock(): void
    {
        $type = $this->ticketType(10);
        $order = $this->realOrder($type, 2);
        $admin = $this->makeUser(10, 'admin');

        $this->actingAs($admin)->patchJson(route('admin.orders.update-status', $order), ['payment_status' => 'failed'])->assertOk();
        $this->assertSame(0, $type->fresh()->quantity_sold);
        $this->actingAs($admin)->patchJson(route('admin.orders.update-status', $order), ['payment_status' => 'paid'])->assertOk();

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame(2, Ticket::where('status', 'sold')->count());
        $this->assertSame(2, $type->fresh()->quantity_sold, 'quantity_sold doit refléter 2 billets, pas 4');
        // I1 : le promoteur est rémunéré pour la vente reconfirmée (une seule commission, repassée pending)
        $this->assertSame(['pending'], Commission::where('order_id', $order->id)->pluck('status')->all());
    }
}
