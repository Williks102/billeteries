<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/**
 * Vues acheteur : QR visible uniquement après paiement, instructions de paiement pour une commande en attente,
 * bandeau du mode démo, accès aux billets d'autrui refusé.
 */
class BuyerTicketViewsTest extends TestCase
{
    use BuildsOrderSchema;

    private const PNG = 'data:image/png;base64,';

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        Mail::fake();
        // Production : tickets.order_item_id existe (lien utilisé par l'autorisation du contrôleur).
        \Illuminate\Support\Facades\Schema::table('tickets', fn ($t) => $t->unsignedBigInteger('order_item_id')->nullable());
        View::share('demoMode', false);
        config(['payments.manual_methods.bank_transfer' => [
            'label' => 'Virement bancaire', 'instructions' => 'Virez le montant sur le compte ci-dessous.',
            'details' => ['IBAN' => 'CI00-TEST-1234'],
        ]]);
    }

    /** Commande de 1 billet ; payée => billet sold + QR en base ; sinon billet réservé. */
    private function order(bool $paid, int $userId = 1): array
    {
        $order = $this->reservedOrder($this->ticketType(10), 1, 'pending', now()->addHours(5));
        $order->update(['user_id' => $userId]);
        $ticket = Ticket::first();
        $ticket->update(['order_item_id' => $order->orderItems()->first()->id]);
        if ($paid) {
            $order->update(['payment_status' => 'paid', 'paid_at' => now()]);
            $ticket->update(['status' => 'sold', 'qr_code' => self::PNG . 'QUJDREVGRw==']);
        } else {
            // Une valeur laissée en base avant paiement ne doit JAMAIS être affichée.
            $ticket->update(['qr_code' => self::PNG . 'RUZHSElKSw==']);
        }

        return [$order->fresh(), $ticket->fresh()];
    }

    // ===== (a) page billet =====

    public function test_issued_ticket_page_shows_qr_data_uri_and_event_details(): void
    {
        [$order, $ticket] = $this->order(true);
        $this->actingAs(User::find(1))->get(route('acheteur.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('<img src="' . self::PNG, false)
            ->assertSee('Concert')->assertSee('Palais')->assertSee($ticket->ticket_code)
            ->assertViewHas('isIssued', true);
    }

    public function test_unpaid_ticket_page_has_no_qr_but_waiting_message_and_order_link(): void
    {
        [$order, $ticket] = $this->order(false);
        $res = $this->actingAs(User::find(1))->get(route('acheteur.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('En attente de confirmation du paiement')
            ->assertSee(route('acheteur.orders.show', $order))
            ->assertViewHas('qrDataUri', null);
        $this->assertStringNotContainsString('data:image', $res->getContent());
        $this->assertStringNotContainsString('RUZHSElKSw', $res->getContent());
    }

    // ===== (b) accès =====

    public function test_other_buyer_cannot_see_a_ticket(): void
    {
        [, $ticket] = $this->order(true);
        $this->actingAs($this->makeUser(30, 'acheteur'))
            ->get(route('acheteur.tickets.show', $ticket))->assertForbidden();
        auth()->logout();
        $this->get(route('acheteur.tickets.show', $ticket))->assertRedirect();
    }

    // ===== (d) détail de commande =====

    public function test_paid_order_detail_shows_qr_and_pending_order_shows_instructions_without_qr(): void
    {
        [$paid] = $this->order(true);
        $this->actingAs(User::find(1))->get(route('acheteur.orders.show', $paid))
            ->assertOk()->assertSee('<img src="' . self::PNG, false)->assertDontSee('En attente de paiement');

        DB::table('orders')->delete();
        DB::table('tickets')->delete();
        DB::table('order_tickets')->delete();
        [$pending] = $this->order(false);
        $pending->update(['payment_method' => 'bank_transfer']);

        $res = $this->actingAs(User::find(1))->get(route('acheteur.orders.show', $pending->fresh()))
            ->assertOk()
            ->assertSee('En attente de paiement')
            ->assertSee('Virement bancaire')->assertSee('CI00-TEST-1234')
            ->assertSee($pending->expires_at->format('d/m/Y à H:i'))
            ->assertViewHas('awaitingPayment', true);
        $this->assertStringNotContainsString('data:image', $res->getContent());
    }

    public function test_paid_order_with_a_non_issued_ticket_shows_no_qr(): void
    {
        [$order, $ticket] = $this->order(true);
        $ticket->update(['status' => 'reserved']); // billet pas (encore) émis, QR résiduel en base
        $res = $this->actingAs(User::find(1))->get(route('acheteur.orders.show', $order))->assertOk();
        $this->assertStringNotContainsString('data:image', $res->getContent());
    }

    public function test_demo_paid_order_mentions_simulated_payment(): void
    {
        [$order] = $this->order(true);
        $order->update(['payment_reference' => \App\Services\CheckoutService::DEMO_REFERENCE]);
        $this->actingAs(User::find(1))->get(route('acheteur.orders.show', $order))
            ->assertOk()->assertSee('MODE DÉMO : paiement simulé');
    }

    public function test_orders_list_and_my_tickets(): void
    {
        [$order] = $this->order(false);
        $this->actingAs(User::find(1))->get(route('acheteur.orders.index'))
            ->assertOk()->assertSee('En attente de paiement')
            ->assertSee($order->expires_at->format('d/m/Y H:i'));
        $res = $this->actingAs(User::find(1))->get(route('acheteur.tickets.index'))->assertOk();
        $this->assertStringNotContainsString('data:image', $res->getContent(), 'jamais de QR pour une commande non payée');
    }

    public function test_my_tickets_shows_qr_for_paid_orders(): void
    {
        $this->order(true);
        $this->actingAs(User::find(1))->get(route('acheteur.tickets.index'))
            ->assertOk()->assertSee('data-qr-src="' . self::PNG, false);
    }

    // ===== (c) bandeau démo =====

    public function test_banner_partial_depends_on_demo_mode(): void
    {
        $this->assertStringContainsString('MODE DÉMO : paiement simulé', view('partials.demo-banner', ['demoMode' => true])->render());
        $this->assertStringNotContainsString('MODE DÉMO', view('partials.demo-banner', ['demoMode' => false])->render());
        $this->assertStringContainsString('@if(!empty($demoMode))', file_get_contents(resource_path('views/partials/demo-banner.blade.php')));
    }

    public function test_every_used_layout_includes_the_banner(): void
    {
        foreach (['app', 'acheteur', 'promoteur', 'admin'] as $layout) {
            $this->assertStringContainsString("@include('partials.demo-banner')",
                file_get_contents(resource_path("views/layouts/$layout.blade.php")), "layout $layout");
        }
    }

    public function test_banner_rendered_in_real_pages_only_in_demo_mode(): void
    {
        [$order] = $this->order(false);
        $order->update(['guest_token' => 'tok123', 'payment_method' => 'bank_transfer']);
        $user = User::find(1);

        foreach ([true, false] as $demo) {
            View::share('demoMode', $demo);
            // layouts.acheteur
            $a = $this->actingAs($user)->get(route('acheteur.orders.show', $order))->assertOk()->getContent();
            // layouts.app
            $b = $this->withSession(['guest_order_tokens.' . $order->id => 'tok123'])
                ->get(route('checkout.guest.confirmation', 'tok123'))->assertOk()->getContent();
            foreach ([$a, $b] as $html) {
                $demo
                    ? $this->assertStringContainsString('data-demo-banner', $html)
                    : $this->assertStringNotContainsString('data-demo-banner', $html);
            }
        }
    }

    public function test_banner_in_promoteur_and_admin_layouts(): void
    {
        foreach (['promoteur', 'admin'] as $layout) {
            foreach ([true, false] as $demo) {
                View::share('demoMode', $demo);
                $this->actingAs(\App\Models\User::find($layout === 'admin' ? 40 : 41) ?? $this->makeUser($layout === 'admin' ? 40 : 41, $layout));
                $file = tempnam(sys_get_temp_dir(), 'lay') . '.blade.php';
                file_put_contents($file, "@extends('layouts.$layout')\n@section('content')x@endsection");
                try {
                    $level = ob_get_level();
                    $html = view()->file($file)->render();
                    // Ces layouts laissent un tampon de sortie ouvert (défaut ANTÉRIEUR, vérifié sur HEAD) : on le referme.
                    while (ob_get_level() > $level) {
                        ob_end_clean();
                    }
                } finally {
                    @unlink($file);
                }
                $demo
                    ? $this->assertStringContainsString('data-demo-banner', $html, $layout)
                    : $this->assertStringNotContainsString('data-demo-banner', $html, $layout);
            }
        }
    }

    // ===== (f) pages de confirmation =====

    public function test_confirmation_pages_show_demo_message_when_simulated(): void
    {
        [$order, $ticket] = $this->order(true);
        $order->update(['guest_token' => 'tok456', 'payment_reference' => \App\Services\CheckoutService::DEMO_REFERENCE]);

        $this->actingAs(User::find(1))->get(route('checkout.confirmation', $order))
            ->assertOk()->assertViewHas('demoSimulatedPayment', true)->assertSee('MODE DÉMO : paiement simulé');
        auth()->logout();
        $this->flushSession();
        $this->withSession(['guest_order_tokens.' . $order->id => 'tok456'])
            ->get(route('checkout.guest.confirmation', 'tok456'))
            ->assertOk()->assertViewHas('demoSimulatedPayment', true)->assertSee('MODE DÉMO : paiement simulé');
    }

    public function test_confirmation_pending_shows_instructions_deadline_and_no_qr(): void
    {
        [$order] = $this->order(false);
        $order->update(['guest_token' => 'tok789', 'payment_method' => 'bank_transfer']);
        $res = $this->actingAs(User::find(1))->get(route('checkout.confirmation', $order))
            ->assertOk()->assertSee('CI00-TEST-1234')->assertSee('À régler avant le')
            ->assertSee($order->expires_at->format('d/m/Y à H:i'))->assertDontSee('MODE DÉMO');
        $this->assertStringNotContainsString('data:image', $res->getContent());
    }
}
