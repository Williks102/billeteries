<?php

namespace Tests\Feature;

use App\Models\Commission;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use App\Services\OrderPaymentService;
use App\Services\QRCodeService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Yaml\Yaml;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/**
 * QR visible uniquement après paiement (data-URI en base, aucun fichier) et MODE DÉMO de confirmation automatique
 * (désactivé par défaut, ne contourne ni validation ni plafonds).
 */
class QrAndDemoModeTest extends TestCase
{
    use BuildsOrderSchema;

    private const PREFIX = 'data:image/png;base64,';

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        Mail::fake();
        config(['payments.max_pending_orders' => 1000, 'payments.max_pending_orders_per_ip' => 1000]);
    }

    private function guestPost($type, int $qty, array $override = [])
    {
        return $this->withSession(['cart' => $this->tamperedCart($type, $qty)])
            ->post(route('checkout.guest.process'), array_merge([
                'email' => 'demo@example.test', 'first_name' => 'Awa', 'last_name' => 'Kone',
                'phone' => '0102030405', 'terms_accepted' => '1', 'payment_method' => 'cash',
            ], $override));
    }

    private function userPost(User $user, $type, int $qty)
    {
        return $this->actingAs($user)->withSession(['cart' => $this->tamperedCart($type, $qty)])
            ->post(route('checkout.process'), [
                'billing_email' => 'u@example.test', 'billing_phone' => '0102030405', 'terms_accepted' => '1', 'payment_method' => 'cash',
            ]);
    }

    // ===== Défaut sûr =====

    public function test_demo_mode_is_disabled_by_default_and_orders_stay_pending(): void
    {
        $this->assertFalse(config('payments.demo_auto_confirm'), 'défaut : désactivé');
        $this->assertFalse(app('view')->shared('demoMode'));

        $type = $this->ticketType(10);
        $this->guestPost($type, 2)->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame('pending', $order->payment_status);
        $this->assertNull($order->payment_reference);
        $this->assertSame(2, Ticket::where('status', 'reserved')->count());
        $this->assertSame(0, Ticket::whereNotNull('qr_code')->count(), 'commande en attente : aucun QR');
        $this->assertSame(0, Payment::count());
    }

    public function test_the_demo_key_is_documented_and_never_enabled_in_deployment_files(): void
    {
        $env = (string) file_get_contents(base_path('.env.example'));
        $this->assertMatchesRegularExpression('/^DEMO_AUTO_CONFIRM_PAYMENTS=false$/m', $env);
        $this->assertStringContainsString('les billets deviennent gratuits', $env);

        $yaml = (string) file_get_contents(base_path('render.yaml'));
        $this->assertStringContainsString('les billets deviennent gratuits', $yaml);
        $vars = collect(Yaml::parse($yaml)['services'][0]['envVars'])->keyBy('key');
        $this->assertFalse($vars['DEMO_AUTO_CONFIRM_PAYMENTS']['sync'] ?? null, 'sync: false');
        $this->assertArrayNotHasKey('value', $vars['DEMO_AUTO_CONFIRM_PAYMENTS']);
    }

    // ===== Mode démo actif =====

    public function test_demo_guest_checkout_confirms_the_order_with_tickets_qr_and_one_commission(): void
    {
        config(['payments.demo_auto_confirm' => true]);
        $type = $this->ticketType(10);

        $response = $this->guestPost($type, 3);
        $response->assertRedirect()->assertSessionHas('success');
        $this->assertStringContainsString('MODE DÉMO : paiement simulé', session('success'));

        $order = Order::firstOrFail();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('DEMO-AUTO', $order->payment_reference);
        $this->assertNull($order->expires_at);
        $this->assertSame(3, Ticket::where('status', 'sold')->count());
        $this->assertSame(3, Ticket::where('qr_code', 'like', self::PREFIX . '%')->count(), 'QR présents après confirmation');
        $this->assertSame(1, Commission::where('order_id', $order->id)->count(), 'commission unique');
        $this->assertSame(1, Payment::count());
        $this->assertSame(3, $type->fresh()->quantity_sold, 'stock décompté une seule fois');
        $this->assertSame([], Storage::allFiles(), 'aucun fichier écrit');

        $this->get($response->headers->get('Location'))->assertOk()->assertViewHas('demoSimulatedPayment', true)
            ->assertViewHas('awaitingPayment', false);
    }

    public function test_demo_authenticated_checkout_confirms_every_order_of_a_multi_event_cart(): void
    {
        config(['payments.demo_auto_confirm' => true]);
        $this->makeUser(5, 'acheteur');
        $type = $this->ticketType(10);

        $response = $this->userPost(User::find(5), $type, 2);
        $response->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('DEMO-AUTO', $order->payment_reference);
        $this->assertSame(2, Ticket::where('status', 'sold')->whereNotNull('qr_code')->count());
        $this->assertSame(1, Commission::count());
        $this->assertSame(1, Payment::count());
        $this->actingAs(User::find(5))->get($response->headers->get('Location'))->assertOk()->assertViewHas('demoSimulatedPayment', true);
    }

    public function test_demo_mode_does_not_bypass_validation_nor_caps(): void
    {
        config(['payments.demo_auto_confirm' => true]);
        $type = $this->ticketType(500);

        // validation : email invalide, conditions non acceptées
        $this->guestPost($type, 1, ['email' => 'pas-un-email'])->assertSessionHasErrors('email');
        $this->guestPost($type, 1, ['terms_accepted' => null])->assertSessionHasErrors('terms_accepted');
        // plafond de billets par commande (10) et stock
        $this->guestPost($type, 11)->assertSessionHas('error');
        $small = $this->ticketType(2);
        $this->guestPost($small, 3)->assertSessionHas('error');
        // moyen de paiement inconnu
        $this->guestPost($type, 1, ['payment_method' => 'card'])->assertSessionHasErrors('payment_method');

        $this->assertSame(0, Order::count(), 'aucune commande créée, donc rien à confirmer');
        $this->assertSame(0, Payment::count());
        $this->assertSame(0, $small->fresh()->quantity_sold);

        // plafond de commandes en attente/par acheteur : le mode démo confirme APRÈS ces contrôles
        config(['payments.max_pending_tickets_per_buyer' => 2]);
        $this->assertSame('paid', (function () use ($type) {
            $this->guestPost($type, 2)->assertRedirect();

            return Order::firstOrFail()->payment_status;
        })());
    }

    public function test_a_demo_confirmation_failure_leaves_the_order_pending_without_breaking_checkout(): void
    {
        config(['payments.demo_auto_confirm' => true]);
        $this->app->instance(OrderPaymentService::class, new class extends OrderPaymentService {
            public function confirmPayment(Order $order, ?string $reference = null, ?User $confirmedBy = null): Order
            {
                throw new \RuntimeException('boom');
            }
        });

        $this->guestPost($this->ticketType(5), 1)->assertRedirect();

        $this->assertSame('pending', Order::firstOrFail()->payment_status);
    }

    public function test_the_demo_flag_is_shared_with_views_when_enabled(): void
    {
        config(['payments.demo_auto_confirm' => true]);
        (new \App\Providers\AppServiceProvider($this->app))->boot();

        $this->assertTrue(app('view')->shared('demoMode'));
    }

    // ===== QR : visible seulement une fois émis =====

    public function test_qr_data_uri_is_null_until_the_ticket_is_issued_then_persisted_and_idempotent(): void
    {
        $type = $this->ticketType(5);
        $order = $this->reservedOrder($type, 1, 'pending', now()->addDay());
        $ticket = Ticket::firstOrFail();

        $this->assertFalse($ticket->isIssued());
        $this->assertNull($ticket->qrDataUri());
        $ticket->update(['status' => 'sold']); // statut vendu mais commande NON payée
        $this->assertFalse($ticket->fresh()->isIssued());
        $this->assertNull($ticket->fresh()->qrDataUri());
        $this->assertNull($ticket->fresh()->qr_code_url);
        $this->assertSame('', $ticket->fresh()->getQrCodeSvg());
        $this->assertNull(app(QRCodeService::class)->getOrGenerateTicketQR($ticket->fresh(), 'base64'));

        $order->update(['payment_status' => 'paid']);
        $ticket = $ticket->fresh();
        $this->assertTrue($ticket->isIssued());
        $uri = $ticket->qrDataUri();
        $this->assertStringStartsWith(self::PREFIX, $uri);
        $this->assertLessThan(60000, strlen($uri), 'tient largement dans une colonne text (64 Ko)');
        $this->assertSame($uri, DB::table('tickets')->where('id', $ticket->id)->value('qr_code'), 'persisté');
        $this->assertSame($uri, $ticket->fresh()->qrDataUri(), 'idempotent');
        $this->assertSame($uri, $ticket->fresh()->qr_code_url);

        $ticket->update(['status' => 'cancelled']);
        $this->assertNull($ticket->fresh()->qrDataUri(), 'billet annulé : pas de QR');
    }

    public function test_confirmation_generates_qr_for_all_tickets_and_a_missing_one_is_regenerated_on_demand(): void
    {
        $type = $this->ticketType(5);
        $order = $this->reservedOrder($type, 3, 'pending', now()->addDay());
        $this->assertSame(0, Ticket::whereNotNull('qr_code')->count());

        app(OrderPaymentService::class)->confirmPayment($order, 'REF');
        $this->assertSame(3, Ticket::whereNotNull('qr_code')->count());

        $ticket = Ticket::first();
        $ticket->update(['qr_code' => null]);
        $this->assertStringStartsWith(self::PREFIX, $ticket->fresh()->qrDataUri());
        $this->assertNotNull($ticket->fresh()->qr_code);
    }

    public function test_legacy_non_data_uri_qr_value_is_regenerated(): void
    {
        $order = $this->reservedOrder($this->ticketType(2), 1, 'paid');
        $ticket = Ticket::firstOrFail();
        $ticket->update(['status' => 'sold', 'qr_code' => 'https://exemple.test/verify-ticket/X']);

        $this->assertStringStartsWith(self::PREFIX, $ticket->fresh()->qrDataUri());
    }

    public function test_backfill_command_is_idempotent_and_skips_unpaid_orders(): void
    {
        $type = $this->ticketType(10);
        $paid = $this->reservedOrder($type, 2, 'paid');
        Ticket::query()->update(['status' => 'sold']);
        $pending = $this->reservedOrder($type, 1, 'pending', now()->addDay());
        Ticket::where('status', 'reserved')->update(['status' => 'sold']); // incohérence : vendu mais commande en attente

        $this->assertSame(0, Artisan::call('tickets:generate-qr'));
        $this->assertStringContainsString('QR générés : 2', Artisan::output());
        $this->assertSame(2, Ticket::whereNotNull('qr_code')->count());

        $this->assertSame(0, Artisan::call('tickets:generate-qr'));
        $this->assertStringContainsString('QR générés : 0', Artisan::output(), 'idempotent');
        $this->assertSame([], Storage::allFiles());
    }

    // ===== Statuts exposés =====

    public function test_order_status_helpers_and_buyer_order_page_variables(): void
    {
        $this->makeUser(7, 'acheteur');
        $type = $this->ticketType(5);
        $order = $this->reservedOrder($type, 1, 'pending', now()->addDay());
        $order->update(['user_id' => 7]);

        $this->assertTrue($order->fresh()->isAwaitingPayment());
        $this->assertEqualsWithDelta(now()->addDay()->timestamp, $order->fresh()->paymentDeadline()->timestamp, 5);

        $this->actingAs(User::find(7))->get(route('acheteur.orders.show', $order))
            ->assertViewHas('awaitingPayment', true)
            ->assertViewHas('paymentInfo', fn ($info) => $info['label'] === 'Virement bancaire' && array_key_exists('IBAN / RIB', $info['details']))
            ->assertViewHas('paymentDeadline');

        app(OrderPaymentService::class)->confirmPayment($order, 'R');
        $paid = $order->fresh();
        $this->assertFalse($paid->isAwaitingPayment());
        $this->assertNull($paid->paymentDeadline());
        $this->assertTrue(Ticket::first()->isIssued());
    }
}
