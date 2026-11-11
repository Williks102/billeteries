<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/**
 * GET /acheteur/orders/{order}/download : le PDF contient les billets et leurs QR codes.
 * Avant ce correctif, la méthode n'avait AUCUNE vérification (IDOR : n'importe quel acheteur connecté
 * pouvait télécharger le PDF de la commande d'un autre en changeant l'identifiant).
 */
class OrderDownloadAuthorizationTest extends TestCase
{
    use BuildsOrderSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        Mail::fake();
    }

    private function order(string $status, int $ownerId): Order
    {
        $order = $this->reservedOrder($this->ticketType(10), 1, 'pending', now()->addHours(5));
        $order->update(['user_id' => $ownerId, 'payment_status' => $status]);

        return $order->fresh();
    }

    private function buyer(int $id, string $role = 'acheteur'): User
    {
        $user = User::find($id) ?? User::create(['name' => "Acheteur $id", 'email' => "a$id@test.test", 'password' => 'x', 'role' => $role]);
        $user->forceFill(['role' => $role])->save();

        return $user;
    }

    public function test_another_buyer_cannot_download_someone_elses_order_pdf(): void
    {
        $order = $this->order('paid', 1);
        $this->buyer(1);
        $intruder = $this->buyer(2);

        $this->actingAs($intruder)->get(route('acheteur.orders.download', $order))->assertForbidden();
    }

    public function test_unpaid_order_has_no_pdf_even_for_its_owner(): void
    {
        $order = $this->order('pending', 1);
        $owner = $this->buyer(1);

        $this->actingAs($owner)->get(route('acheteur.orders.download', $order))
            ->assertRedirect(route('acheteur.orders.show', $order))
            ->assertSessionHas('error');
    }

    public function test_owner_of_a_paid_order_gets_the_pdf(): void
    {
        $order = $this->order('paid', 1);
        $owner = $this->buyer(1);

        Pdf::shouldReceive('loadView')->once()->andReturnSelf();
        Pdf::shouldReceive('setPaper')->andReturnSelf();
        Pdf::shouldReceive('setOptions')->andReturnSelf();
        Pdf::shouldReceive('download')->once()->andReturn(response('PDF-OK', 200));

        $this->actingAs($owner)->get(route('acheteur.orders.download', $order))->assertOk()->assertSee('PDF-OK');
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $order = $this->order('paid', 1);

        $this->get(route('acheteur.orders.download', $order))->assertRedirect();
    }
}
