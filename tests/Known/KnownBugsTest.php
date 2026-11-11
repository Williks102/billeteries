<?php

namespace Tests\Known;

use App\Models\Ticket;
use App\Services\CheckoutService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/**
 * BUGS CONNUS (tests ROUGES volontairement). Fichier EXCLU de `composer run check` (script test:known-bugs
 * séparé) pour ne pas bloquer le hook pre-push. À déplacer dans tests/Feature une fois corrigés.
 */
class KnownBugsTest extends TestCase
{
    use BuildsOrderSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        Mail::fake();
    }

    /**
     * BUG (agent responsable : paiements-stock, Admin/OrderController::updateStatus/update/bulkAction mark_failed) :
     * failed via l'admin n'appelle pas releaseStock() (stock toujours compté). Si l'admin passe ensuite la commande à
     * paid, OrderPaymentService voit « failed » et appelle reclaimStock() qui réserve à NOUVEAU : quantity_sold
     * double (2 billets -> 4 comptés) alors que 2 billets existent. Survente fantôme : le stock vendable diminue
     * à tort.
     */
    public function test_failed_by_admin_then_paid_must_not_double_count_stock(): void
    {
        $type = $this->ticketType(10);
        $service = app(CheckoutService::class);
        $groups = $service->buildEventGroups([['ticket_type_id' => $type->id, 'quantity' => 2]]);
        $order = DB::transaction(fn () => $service->createOrder(1, 1, $groups[1], ['payment_method' => 'cash']));
        $admin = $this->makeUser(10, 'admin');

        $this->actingAs($admin)->patchJson(route('admin.orders.update-status', $order), ['payment_status' => 'failed'])->assertOk();
        $this->actingAs($admin)->patchJson(route('admin.orders.update-status', $order), ['payment_status' => 'paid'])->assertOk();

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame(2, Ticket::where('status', 'sold')->count());
        $this->assertSame(2, $type->fresh()->quantity_sold, 'quantity_sold doit refléter 2 billets, pas 4');
    }

    /**
     * BUG (agent responsable : paiements-stock, GuestCheckoutController::process, règle 'terms_accepted' => 'required') :
     * 'required' accepte "0" et "false" : un client HTTP peut passer le checkout invité en refusant explicitement
     * les CGU. Le checkout connecté utilise `required|accepted` (correct). Correctif : `required|accepted`.
     */
    public function test_guest_checkout_must_refuse_terms_explicitly_declined(): void
    {
        $type = $this->ticketType(10);

        $this->withSession(['cart' => $this->tamperedCart($type, 1)])
            ->post(route('checkout.guest.process'), [
                'email' => 'invite@example.test', 'first_name' => 'Awa', 'last_name' => 'Kone',
                'phone' => '0102030405', 'terms_accepted' => '0', 'payment_method' => 'cash',
            ])->assertSessionHasErrors('terms_accepted');

        $this->assertSame(0, \App\Models\Order::count());
    }
}
