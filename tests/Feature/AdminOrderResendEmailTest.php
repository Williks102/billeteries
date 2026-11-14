<?php

namespace Tests\Feature;

use App\Mail\PaymentConfirmation;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/**
 * « Renvoyer l'email » de la liste des commandes (fetch) : le bouton lit du JSON. Avant, la méthode répondait par une
 * redirection : le bouton affichait « erreur » même quand le mail était parti. Le faux renvoi `admin.emails.resend`
 * (qui répondait « succès » sans rien envoyer) est supprimé.
 */
class AdminOrderResendEmailTest extends TestCase
{
    use BuildsOrderSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        Mail::fake();
        View::share('demoMode', false);
        $this->makeUser(10, 'admin');
        $this->makeUser(2, 'promoteur');
    }

    private function order(string $paymentStatus): Order
    {
        $order = $this->reservedOrder($this->ticketType(10), 1, 'pending', now()->addHours(5));
        $order->update(['user_id' => 1, 'payment_status' => $paymentStatus, 'paid_at' => $paymentStatus === 'paid' ? now() : null]);

        return Order::find($order->id);
    }

    public function test_admin_gets_json_success_and_the_mail_is_queued(): void
    {
        $order = $this->order('paid');

        $this->actingAs(\App\Models\User::find(10))
            ->postJson(route('admin.orders.resend-email', $order))
            ->assertOk()
            ->assertJson(['success' => true]);

        Mail::assertQueued(PaymentConfirmation::class, 1);
    }

    public function test_unpaid_order_is_refused_in_json_and_nothing_is_sent(): void
    {
        $order = $this->order('pending');

        $this->actingAs(\App\Models\User::find(10))
            ->postJson(route('admin.orders.resend-email', $order))
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        Mail::assertNothingQueued();
    }

    public function test_a_classic_form_post_still_redirects_back_with_a_flash(): void
    {
        $order = $this->order('paid');

        $this->actingAs(\App\Models\User::find(10))
            ->from('/admin/orders')
            ->post(route('admin.orders.resend-email', $order))
            ->assertRedirect('/admin/orders')
            ->assertSessionHas('success');
    }

    public function test_non_admins_and_guests_cannot_resend(): void
    {
        $order = $this->order('paid');

        $this->actingAs(\App\Models\User::find(2))->postJson(route('admin.orders.resend-email', $order))->assertStatus(403);
        auth()->logout();
        $this->postJson(route('admin.orders.resend-email', $order))->assertStatus(401);
        Mail::assertNothingQueued();
    }

    public function test_the_button_script_asks_for_json(): void
    {
        $js = file_get_contents(public_path('js/admin-orders-index.js'));

        $this->assertMatchesRegularExpression("/resend-email.{0,200}'Accept': 'application\/json'/s", $js);
    }

    public function test_the_misleading_stub_route_is_gone(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('admin.emails.resend'));
        $this->assertFalse(method_exists(\App\Http\Controllers\Admin\AdminController::class, 'resendOrderEmail'));
    }

    public function test_storage_used_is_not_a_misleading_zero_on_a_remote_disk(): void
    {
        config(['filesystems.media_disk' => 'r2']);
        DB::table('users')->where('id', 10)->update(['role' => 'admin']);

        $this->actingAs(\App\Models\User::find(10))
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Hébergé hors du serveur');
    }
}
