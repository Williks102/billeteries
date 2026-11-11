<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/** Route admin de confirmation manuelle et chemins voisins délégués à OrderPaymentService. */
class AdminConfirmPaymentTest extends TestCase
{
    use BuildsOrderSchema;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        Mail::fake();

        DB::table('users')->insert(['id' => 10, 'name' => 'Admin', 'email' => 'admin@example.test', 'role' => 'admin']);
        $this->admin = User::find(10);
    }

    public function test_admin_confirms_payment_and_trace_records_who(): void
    {
        $order = $this->reservedOrder($this->ticketType(5), 1);

        $this->actingAs($this->admin)
            ->post(route('admin.orders.confirm-payment', $order), ['payment_reference' => 'MM-42'])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('success');

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('MM-42', $order->fresh()->payment_reference);
        $this->assertSame(10, Payment::sole()->provider_response['confirmed_by']);

        // Rejeu : idempotent
        $this->actingAs($this->admin)->post(route('admin.orders.confirm-payment', $order))->assertRedirect();
        $this->assertSame(1, Payment::count());
    }

    public function test_reference_is_limited_to_100_characters_and_non_admin_is_forbidden(): void
    {
        $order = $this->reservedOrder($this->ticketType(5), 1);

        $this->actingAs($this->admin)
            ->post(route('admin.orders.confirm-payment', $order), ['payment_reference' => str_repeat('x', 101)])
            ->assertSessionHasErrors('payment_reference');

        $this->actingAs(User::find(1))
            ->post(route('admin.orders.confirm-payment', $order))
            ->assertForbidden();
        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_expired_order_without_stock_returns_clear_error_json(): void
    {
        $type = $this->ticketType(1);
        $order = $this->reservedOrder($type, 1);
        $order->releaseStock();
        $type->fresh()->reserveTickets(1);

        $this->actingAs($this->admin)
            ->postJson(route('admin.orders.confirm-payment', $order))
            ->assertStatus(422)
            ->assertJsonPath('success', false);
        $this->assertSame('failed', $order->fresh()->payment_status);
    }

    public function test_update_status_paid_delegates_and_is_idempotent(): void
    {
        $order = $this->reservedOrder($this->ticketType(5), 1);

        $this->actingAs($this->admin)->patchJson(route('admin.orders.update-status', $order), ['payment_status' => 'paid'])
            ->assertOk();
        $this->actingAs($this->admin)->patchJson(route('admin.orders.update-status', $order), ['payment_status' => 'paid'])
            ->assertOk();

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame(1, Payment::count());
        $this->assertNotNull($order->fresh()->paid_at);
        $this->assertNull($order->fresh()->expires_at);
    }

    public function test_buyer_confirmation_page_shows_instructions_and_is_owner_only(): void
    {
        $order = $this->reservedOrder($this->ticketType(5), 1, 'pending', now()->addDays(2));
        $order->update(['user_id' => 1]);
        DB::table('users')->insert(['id' => 20, 'name' => 'Autre', 'email' => 'x@example.test', 'role' => 'acheteur']);

        $this->actingAs(User::find(1))->get(route('checkout.confirmation', $order))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee(\App\Helpers\CurrencyHelper::formatNumber($order->total_amount))
            ->assertSee('Virement bancaire')
            ->assertSee($order->expires_at->format('d/m/Y à H:i'));

        $this->actingAs(User::find(20))->get(route('checkout.confirmation', $order))->assertForbidden();
    }
}
