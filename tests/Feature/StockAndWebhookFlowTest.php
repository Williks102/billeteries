<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Ticket;
use App\Models\TicketType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * P3/P4 : idempotence du webhook, montant, stock atomique et libération.
 * Schéma SQLite minimal créé ici (les migrations du projet utilisent du SQL MySQL).
 */
class StockAndWebhookFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:' . base64_encode(str_repeat('k', 32))]);
        config([
            'payments.providers.paiementpro.merchant_id' => 'TEST',
            'payments.providers.paiementpro.secret_key' => 's3cret',
        ]);

        Schema::create('ticket_types', function ($t) {
            $t->id();
            $t->unsignedBigInteger('event_id')->default(1);
            $t->string('name')->default('Std');
            $t->text('description')->nullable();
            $t->integer('price')->default(1000);
            $t->integer('quantity_available')->default(0);
            $t->integer('quantity_sold')->default(0);
            $t->dateTime('sale_start_date')->nullable();
            $t->dateTime('sale_end_date')->nullable();
            $t->integer('max_per_order')->default(10);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('orders', function ($t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->default(1);
            $t->unsignedBigInteger('event_id')->default(1);
            $t->integer('total_amount');
            $t->string('payment_status')->default('pending');
            $t->string('payment_method')->nullable();
            $t->string('payment_reference')->nullable();
            $t->string('order_number')->unique();
            $t->string('billing_email')->default('a@b.c');
            $t->string('billing_phone')->nullable();
            $t->string('guest_token')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->timestamp('expires_at')->nullable();
            $t->timestamps();
        });
        Schema::create('order_items', function ($t) {
            $t->id();
            $t->unsignedBigInteger('order_id');
            $t->unsignedBigInteger('ticket_type_id');
            $t->integer('quantity');
            $t->integer('unit_price')->default(0);
            $t->integer('total_price')->default(0);
            $t->timestamps();
        });
        Schema::create('tickets', function ($t) {
            $t->id();
            $t->unsignedBigInteger('ticket_type_id');
            $t->string('ticket_code')->nullable();
            $t->string('status')->default('reserved');
            $t->timestamps();
        });
        Schema::create('order_tickets', function ($t) {
            $t->id();
            $t->unsignedBigInteger('order_id');
            $t->unsignedBigInteger('ticket_id');
            $t->unsignedBigInteger('order_item_id')->nullable();
            $t->timestamps();
        });
        Schema::create('payments', function ($t) {
            $t->id();
            $t->unsignedBigInteger('order_id');
            $t->string('payment_method');
            $t->integer('amount');
            $t->string('status')->default('pending');
            $t->string('provider_transaction_id')->nullable();
            $t->json('provider_response')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
        });
    }

    private function ticketType(int $available, int $sold = 0): TicketType
    {
        return TicketType::create([
            'name' => 'Std', 'price' => 1000,
            'quantity_available' => $available, 'quantity_sold' => $sold,
            'sale_start_date' => now()->subDay(), 'sale_end_date' => now()->addDay(),
            'max_per_order' => 10, 'is_active' => true,
        ]);
    }

    /** Commande pending de $qty billets réservés (stock déjà décompté). */
    private function reservedOrder(TicketType $type, int $qty, string $status = 'pending', $expiresAt = null): Order
    {
        $this->assertTrue($type->reserveTickets($qty));

        $order = Order::create([
            'total_amount' => 1000 * $qty, 'payment_status' => $status,
            'order_number' => 'ORD-' . uniqid(), 'expires_at' => $expiresAt,
        ]);
        $item = $order->orderItems()->create(['ticket_type_id' => $type->id, 'quantity' => $qty]);
        for ($i = 0; $i < $qty; $i++) {
            $ticket = Ticket::create(['ticket_type_id' => $type->id, 'ticket_code' => uniqid(), 'status' => 'reserved']);
            DB::table('order_tickets')->insert([
                'order_id' => $order->id, 'ticket_id' => $ticket->id, 'order_item_id' => $item->id,
            ]);
        }

        return $order;
    }

    private function notify(Order $order, array $override = [])
    {
        $data = array_merge([
            'merchantId' => 'TEST',
            'referenceNumber' => $order->order_number,
            'amount' => $order->total_amount,
            'responsecode' => '0',
            'transactiondt' => '2026-01-01 00:00:00',
        ], $override);
        $data['hashcode'] = $override['hashcode']
            ?? hash('sha256', $data['merchantId'] . $data['referenceNumber'] . $data['amount'] . 's3cret');

        return $this->post('/payments/paiementpro/notification', $data);
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

    public function test_valid_notification_marks_paid_once_and_is_idempotent(): void
    {
        $type = $this->ticketType(5);
        $order = $this->reservedOrder($type, 2);

        $this->notify($order)->assertOk();
        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertNotNull($order->paid_at);
        $this->assertSame(2, Ticket::where('status', 'sold')->count());

        $paidAt = $order->paid_at;
        $this->notify($order)->assertOk();
        $this->assertEquals($paidAt, $order->fresh()->paid_at);
        $this->assertSame(2, $type->fresh()->quantity_sold);
    }

    public function test_amount_mismatch_is_rejected(): void
    {
        $order = $this->reservedOrder($this->ticketType(5), 1);

        $this->notify($order, ['amount' => 1])->assertStatus(400);
        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_failed_notification_releases_stock_but_never_overwrites_paid(): void
    {
        $type = $this->ticketType(5);
        $order = $this->reservedOrder($type, 2);

        $this->notify($order, ['responsecode' => '-1'])->assertOk();
        $this->assertSame('failed', $order->fresh()->payment_status);
        $this->assertSame(0, $type->fresh()->quantity_sold);
        $this->assertSame(2, Ticket::where('status', 'cancelled')->count());

        $paid = $this->reservedOrder($type, 1);
        $this->notify($paid)->assertOk();
        $this->notify($paid, ['responsecode' => '-1'])->assertOk();
        $this->assertSame('paid', $paid->fresh()->payment_status);
        $this->assertSame(1, $type->fresh()->quantity_sold);
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
}
