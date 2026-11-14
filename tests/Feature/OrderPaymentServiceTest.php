<?php

namespace Tests\Feature;

use App\Exceptions\OrderPaymentException;
use App\Mail\PaymentConfirmation;
use App\Models\Commission;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\EmailService;
use App\Services\OrderPaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/** Confirmation manuelle du paiement : idempotence, transitions, stock, commission unique. */
class OrderPaymentServiceTest extends TestCase
{
    use BuildsOrderSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        Mail::fake();
    }

    private function admin(): User
    {
        $admin = new User();
        $admin->id = 99;

        return $admin;
    }

    private function service(): OrderPaymentService
    {
        return app(OrderPaymentService::class);
    }

    public function test_pending_becomes_paid_with_trace_tickets_and_email(): void
    {
        $type = $this->ticketType(5);
        $order = $this->reservedOrder($type, 2, 'pending', now()->addHours(10));

        $result = $this->service()->confirmPayment($order, ' VIR-123 ', $this->admin());

        $this->assertSame('paid', $result->payment_status);
        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertNotNull($order->paid_at);
        $this->assertNull($order->expires_at);
        $this->assertSame('VIR-123', $order->payment_reference);
        $this->assertSame(2, Ticket::where('status', 'sold')->count());
        $this->assertSame(2, $type->fresh()->quantity_sold);

        $payment = Payment::where('order_id', $order->id)->sole();
        $this->assertSame('completed', $payment->status);
        $this->assertSame('bank_transfer', $payment->payment_method);
        $this->assertSame(99, $payment->provider_response['confirmed_by']);
        $this->assertNotNull($payment->completed_at);

        Mail::assertQueued(PaymentConfirmation::class, 1);
    }

    public function test_confirmation_is_idempotent(): void
    {
        $order = $this->reservedOrder($this->ticketType(5), 1);

        $this->service()->confirmPayment($order, 'A', $this->admin());
        $paidAt = $order->fresh()->paid_at;

        $again = $this->service()->confirmPayment($order->fresh(), 'B', $this->admin());

        $this->assertSame('paid', $again->payment_status);
        $this->assertEquals($paidAt, $order->fresh()->paid_at);
        $this->assertSame('A', $order->fresh()->payment_reference);
        $this->assertSame(1, Payment::count());
        Mail::assertQueued(PaymentConfirmation::class, 1);
    }

    public function test_failed_order_is_reclaimed_when_stock_is_available(): void
    {
        $type = $this->ticketType(5);
        $order = $this->reservedOrder($type, 2, 'pending');
        $this->assertTrue($order->releaseStock());
        $this->assertSame(0, $type->fresh()->quantity_sold);

        $this->service()->confirmPayment($order->fresh(), null, $this->admin());

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame(2, $type->fresh()->quantity_sold);
        $this->assertSame(2, Ticket::where('status', 'sold')->count());
    }

    public function test_failed_order_without_stock_raises_clear_exception_and_stays_untouched(): void
    {
        $type = $this->ticketType(2);
        $order = $this->reservedOrder($type, 2, 'pending');
        $this->assertTrue($order->releaseStock());
        $this->assertTrue($type->fresh()->reserveTickets(2)); // stock repris par quelqu'un d'autre

        try {
            $this->service()->confirmPayment($order->fresh(), null, $this->admin());
            $this->fail('OrderPaymentException attendue');
        } catch (OrderPaymentException $e) {
            $this->assertStringContainsString('Stock indisponible', $e->getMessage());
        }

        $this->assertSame('failed', $order->fresh()->payment_status);
        $this->assertSame(2, $type->fresh()->quantity_sold);
        $this->assertSame(0, Payment::count());
        Mail::assertNothingQueued();
    }

    public function test_refunded_order_is_refused(): void
    {
        $order = $this->reservedOrder($this->ticketType(5), 1, 'refunded');

        $this->expectException(OrderPaymentException::class);
        try {
            $this->service()->confirmPayment($order, null, $this->admin());
        } finally {
            $this->assertSame('refunded', $order->fresh()->payment_status);
            $this->assertSame(0, Payment::count());
        }
    }

    public function test_exactly_one_commission_per_order(): void
    {
        $type = $this->ticketType(10);

        // Commande sans commission (ancienne) : créée à la confirmation, une seule fois
        $legacy = $this->reservedOrder($type, 1);
        $this->service()->confirmPayment($legacy, null, $this->admin());
        $this->service()->confirmPayment($legacy->fresh(), null, $this->admin());
        $this->assertSame(1, Commission::where('order_id', $legacy->id)->count());

        // Commande créée par CheckoutService (commission à la création) : pas de doublon
        $groups = app(CheckoutService::class)->buildEventGroups([
            ['ticket_type_id' => $type->id, 'quantity' => 1],
        ]);
        $created = DB::transaction(fn () => app(CheckoutService::class)->createOrder(1, 1, $groups[1], [
            'payment_method' => 'cash',
        ]));
        $this->assertSame(1, Commission::where('order_id', $created->id)->count());

        $this->service()->confirmPayment($created, null, $this->admin());
        $created->fresh()->markAsPaid(); // délégué : idempotent, aucune commission en plus
        $this->assertSame(1, Commission::where('order_id', $created->id)->count());
    }

    public function test_email_failure_does_not_cancel_confirmation(): void
    {
        $this->app->instance(EmailService::class, new class extends EmailService {
            public function sendPaymentConfirmation(Order $order)
            {
                throw new \RuntimeException('smtp down');
            }
        });
        $order = $this->reservedOrder($this->ticketType(5), 1);

        $this->service()->confirmPayment($order, null, $this->admin());

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame(1, Payment::count());
    }
}
