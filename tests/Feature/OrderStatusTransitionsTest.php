<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Ticket;
use App\Services\CheckoutService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/** M2 : matrice centralisée des transitions de statut, appliquée par toutes les voies admin. */
class OrderStatusTransitionsTest extends TestCase
{
    use BuildsOrderSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        Mail::fake();
    }

    public function test_transition_matrix(): void
    {
        $allowed = [
            'pending' => ['paid', 'failed'],
            'processing' => ['paid', 'failed'],
            'failed' => ['paid'],
            'paid' => ['refunded'],
            'refunded' => [],
        ];

        foreach (array_keys($allowed) as $from) {
            foreach (['pending', 'processing', 'paid', 'failed', 'refunded'] as $to) {
                $this->assertSame(
                    in_array($to, $allowed[$from], true),
                    Order::canTransition($from, $to),
                    "$from -> $to"
                );
            }
        }
    }

    /** @return array<string, array{0:string,1:string}> */
    public static function forbiddenPairs(): array
    {
        return [
            'refunded -> paid' => ['refunded', 'paid'],
            'refunded -> pending' => ['refunded', 'pending'],
            'refunded -> failed' => ['refunded', 'failed'],
            'paid -> pending' => ['paid', 'pending'],
            'paid -> failed' => ['paid', 'failed'],
            'failed -> pending' => ['failed', 'pending'],
            'failed -> refunded' => ['failed', 'refunded'],
            'pending -> refunded' => ['pending', 'refunded'],
        ];
    }

    #[DataProvider('forbiddenPairs')]
    public function test_admin_cannot_apply_forbidden_transition_on_any_path(string $from, string $to): void
    {
        $type = $this->ticketType(5);
        $order = $this->reservedOrder($type, 2, $from);
        $stock = $type->fresh()->quantity_sold;
        $admin = $this->makeUser(10, 'admin');

        $this->actingAs($admin)->patchJson(route('admin.orders.update-status', $order), ['payment_status' => $to])
            ->assertStatus(422)->assertJsonPath('success', false);
        $this->actingAs($admin)->put(route('admin.orders.update', $order), ['payment_status' => $to])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'interdite'));

        $this->assertSame($from, $order->fresh()->payment_status);
        $this->assertSame($stock, $type->fresh()->quantity_sold);
        $this->assertSame(0, Payment::count());
    }

    public function test_refunded_never_goes_back_to_sold_tickets(): void
    {
        $type = $this->ticketType(5);
        $service = app(CheckoutService::class);
        $groups = $service->buildEventGroups([['ticket_type_id' => $type->id, 'quantity' => 1]]);
        $order = DB::transaction(fn () => $service->createOrder(1, 1, $groups[1], ['payment_method' => 'cash']));
        $admin = $this->makeUser(10, 'admin');

        $this->actingAs($admin)->patchJson(route('admin.orders.update-status', $order), ['payment_status' => 'paid'])->assertOk();
        $this->actingAs($admin)->patchJson(route('admin.orders.update-status', $order), ['payment_status' => 'refunded'])->assertOk();
        // Contournement historique : refunded -> pending -> paid
        $this->actingAs($admin)->patchJson(route('admin.orders.update-status', $order), ['payment_status' => 'pending'])->assertStatus(422);
        $this->actingAs($admin)->patchJson(route('admin.orders.update-status', $order), ['payment_status' => 'paid'])->assertStatus(422);

        $this->assertSame('refunded', $order->fresh()->payment_status);
        $this->assertSame(0, Ticket::where('status', 'sold')->count());
        $this->assertSame(0, $type->fresh()->quantity_sold);
    }

    public function test_same_status_is_a_noop(): void
    {
        $type = $this->ticketType(5);
        $order = $this->reservedOrder($type, 1, 'pending');

        $this->actingAs($this->makeUser(10, 'admin'))
            ->put(route('admin.orders.update', $order), ['payment_status' => 'pending', 'admin_notes' => 'RAS'])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertSame('RAS', $order->fresh()->admin_notes);
        $this->assertSame(1, $type->fresh()->quantity_sold);
    }
}
