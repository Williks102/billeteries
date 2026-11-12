<?php

namespace Tests\Feature;

use App\Models\Commission;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\OrderPaymentService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/**
 * Commandes GRATUITES : un total serveur de 0 FCFA est confirmé automatiquement (via OrderPaymentService::confirmPayment,
 * référence GRATUIT), sans choix de paiement ni commission ; plafonds dédiés comptés en base.
 *
 * Limite SQLite : lockForUpdate est ignoré ; la concurrence sur le dernier billet est simulée en séquence (le verrou
 * réel est prouvé sur MySQL par tests/MySql). Aucune donnée personnelle réelle : adresses en example.test.
 */
class FreeOrdersTest extends TestCase
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

    private function freeType(int $available = 50, int $price = 0): TicketType
    {
        $type = $this->ticketType($available);
        $type->update(['price' => $price, 'sale_end_date' => now()->addDays(30)]);

        return $type->fresh();
    }

    /** Panier de session aux prix FALSIFIÉS (le prix de la session n'est jamais source de vérité). */
    private function cart(TicketType $type, int $qty, int $fakePrice = 0): array
    {
        return ['k' => [
            'ticket_type_id' => $type->id, 'event_id' => (int) $type->event_id ?: 1, 'quantity' => $qty,
            'ticket_name' => 'Std', 'unit_price' => $fakePrice, 'total_price' => $fakePrice * $qty,
            'event_title' => 'Concert', 'event_date' => '01/01/2027', 'event_venue' => 'Palais', 'event_image' => null,
        ]];
    }

    private function guestPayload(array $o = []): array
    {
        return $o + [
            'email' => 'gratuit@example.test', 'first_name' => 'Awa', 'last_name' => 'Kone',
            'phone' => '0102030405', 'terms_accepted' => '1',
        ];
    }

    private function guestPost(array $cart, array $payload = [], string $ip = '127.0.0.1', string $sessionKey = 'cart')
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])->withSession([$sessionKey => $cart])
            ->post(route('checkout.guest.process'), $this->guestPayload($payload));
    }

    private function userPost(User $user, array $cart, array $payload = [], string $ip = '127.0.0.1')
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])->actingAs($user)->withSession(['cart' => $cart])
            ->post(route('checkout.process'), $payload + [
                'billing_email' => 'u@example.test', 'billing_phone' => '0102030405', 'terms_accepted' => '1',
            ]);
    }

    private function assertConfirmedFree(Order $order, int $qty): void
    {
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('free', $order->payment_method);
        $this->assertSame(CheckoutService::FREE_REFERENCE, $order->payment_reference);
        $this->assertSame(0, (int) $order->total_amount);
        $this->assertNull($order->expires_at);
        $this->assertNotNull($order->paid_at);

        $tickets = Ticket::whereIn('id', DB::table('order_tickets')->where('order_id', $order->id)->pluck('ticket_id'))->get();
        $this->assertCount($qty, $tickets);
        foreach ($tickets as $t) {
            $this->assertSame('sold', $t->status);
            $this->assertStringStartsWith(self::PREFIX, (string) $t->qr_code, 'QR généré après confirmation');
        }

        $this->assertSame(0, Commission::where('order_id', $order->id)->count(), 'aucune commission fantôme sur 0 FCFA');
        $this->assertSame(1, Payment::where('order_id', $order->id)->count());
        $this->assertSame(0, (int) Payment::where('order_id', $order->id)->value('amount'));
    }

    // ===== Les trois parcours =====

    public function test_guest_free_order_is_confirmed_immediately_without_payment_method(): void
    {
        $type = $this->freeType();

        $r = $this->guestPost($this->cart($type, 2));

        $order = Order::firstOrFail();
        $r->assertRedirect(route('checkout.guest.confirmation', $order->guest_token));
        $this->assertConfirmedFree($order, 2);
        $this->assertSame(2, (int) $type->fresh()->quantity_sold, 'stock décrémenté une seule fois');
        // Preuve de possession inchangée : guest_token mémorisé en session
        $this->assertSame($order->guest_token, session('guest_order_tokens.' . $order->id));

        $page = $this->get(route('checkout.guest.confirmation', $order->guest_token));
        $page->assertOk()->assertSee('Commande gratuite')->assertSee('Réservation confirmée')
            ->assertSee(self::PREFIX, false)
            ->assertDontSee('En attente de paiement')->assertDontSee('MODE DÉMO')->assertDontSee('deadline-countdown', false);
    }

    public function test_guest_confirmation_hides_qr_codes_without_session_proof(): void
    {
        $type = $this->freeType();
        $this->guestPost($this->cart($type, 1));
        $order = Order::firstOrFail();

        $this->flushSession();
        $this->get(route('checkout.guest.confirmation', $order->guest_token))
            ->assertOk()->assertSee('Commande gratuite')->assertDontSee(self::PREFIX, false);
    }

    public function test_connected_user_free_order_is_confirmed_and_shows_qr(): void
    {
        $type = $this->freeType();
        $user = User::find(1);

        $r = $this->userPost($user, $this->cart($type, 3));

        $order = Order::firstOrFail();
        $r->assertRedirect(route('checkout.confirmation', $order));
        $this->assertConfirmedFree($order, 3);
        $this->assertSame(3, (int) $type->fresh()->quantity_sold);
        $this->assertNull(session('cart'), 'panier purgé');

        $this->actingAs($user)->get(route('checkout.confirmation', $order))
            ->assertOk()->assertSee('Commande gratuite')->assertSee(self::PREFIX, false)->assertSee('Voir mes billets')
            ->assertDontSee('Mode de paiement');
    }

    public function test_direct_booking_free_order_is_confirmed(): void
    {
        $type = $this->freeType();

        $this->guestPost($this->cart($type, 1), [], '127.0.0.1', 'direct_booking');

        $this->assertConfirmedFree(Order::firstOrFail(), 1);
        $this->assertNull(session('direct_booking'), 'sélection directe purgée');
    }

    public function test_forged_payment_method_on_a_free_order_is_ignored_and_stored_as_free(): void
    {
        $type = $this->freeType();

        $this->guestPost($this->cart($type, 1), ['payment_method' => 'cash']);

        $this->assertConfirmedFree(Order::firstOrFail(), 1);
    }

    public function test_demo_mode_does_not_mark_free_orders_as_simulated_and_keeps_paid_demo_behaviour(): void
    {
        config(['payments.demo_auto_confirm' => true]);
        $free = $this->freeType();
        $this->guestPost($this->cart($free, 1))->assertSessionHas('success', CheckoutService::successMessage(1, true));
        $this->assertSame(CheckoutService::FREE_REFERENCE, Order::firstOrFail()->payment_reference);
        $this->assertStringNotContainsString('DÉMO', (string) session('success'));

        $paid = $this->freeType(10, 1000);
        $this->guestPost($this->cart($paid, 1), ['email' => 'autre@example.test', 'payment_method' => 'cash']);
        $this->assertSame(CheckoutService::DEMO_REFERENCE, Order::orderByDesc('id')->first()->payment_reference);
    }

    // ===== Règle = total == 0 =====

    public function test_paid_cart_with_one_free_ticket_stays_pending_and_requires_payment_method(): void
    {
        $free = $this->freeType();
        $paid = $this->freeType(10, 1000);
        $cart = ['a' => $this->cart($free, 1)['k'], 'b' => $this->cart($paid, 1)['k']];

        // total serveur > 0 : payment_method exigé
        $this->guestPost($cart)->assertSessionHasErrors('payment_method');
        $this->assertSame(0, Order::count());

        $this->guestPost($cart, ['payment_method' => 'cash']);

        $order = Order::firstOrFail();
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame('cash', $order->payment_method);
        $this->assertSame(1000, (int) $order->total_amount);
        $this->assertNotNull($order->expires_at);
        $this->assertSame(1, Commission::count(), 'commission normale sur la commande payante');
        $this->assertSame('reserved', Ticket::first()->status);
    }

    public function test_forged_zero_price_on_a_paid_ticket_is_ignored(): void
    {
        $paid = $this->freeType(10, 1000);

        // Prix à 0 dans la session et dans le formulaire, aucun payment_method : le serveur recalcule 1000 FCFA
        $this->guestPost($this->cart($paid, 1, 0), ['total_amount' => 0, 'total' => 0, 'free' => 1, 'unit_price' => 0])
            ->assertSessionHasErrors('payment_method');
        $this->assertSame(0, Order::count());
        $this->assertSame(0, (int) $paid->fresh()->quantity_sold);

        // payment_method = 'free' falsifié : refusé (méthode inconnue)
        $this->guestPost($this->cart($paid, 1, 0), ['payment_method' => 'free'])->assertSessionHasErrors('payment_method');
        $this->assertSame(0, Order::count());

        // Avec une vraie méthode : pending à 1000 FCFA, jamais confirmé
        $this->guestPost($this->cart($paid, 1, 0), ['payment_method' => 'cash']);
        $order = Order::firstOrFail();
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame(1000, (int) $order->total_amount);
    }

    public function test_connected_checkout_requires_payment_method_for_paid_cart(): void
    {
        $paid = $this->freeType(10, 1000);

        $this->userPost(User::find(1), $this->cart($paid, 1, 0))->assertSessionHasErrors('payment_method');
        $this->assertSame(0, Order::count());
    }

    // ===== Plafonds =====

    public function test_free_orders_per_ip_are_capped_without_reserving_stock(): void
    {
        config(['payments.free_orders_per_ip_per_hour' => 2, 'payments.free_orders_per_email_per_day' => 100]);
        $type = $this->freeType();

        $this->guestPost($this->cart($type, 1), ['email' => 'a1@example.test']);
        $this->guestPost($this->cart($type, 1), ['email' => 'a2@example.test']);
        $r = $this->guestPost($this->cart($type, 1), ['email' => 'a3@example.test']);

        $r->assertSessionHas('error', CheckoutService::FREE_LIMIT_ERROR);
        $this->assertSame(2, Order::count());
        $this->assertSame(2, (int) $type->fresh()->quantity_sold, 'aucun stock réservé au-delà du plafond');

        // Autre IP : non limitée ; l'IP n'est stockée qu'en empreinte HMAC
        $this->guestPost($this->cart($type, 1), ['email' => 'a4@example.test'], '10.1.2.3');
        $this->assertSame(3, Order::count());
        $this->assertSame(0, Order::where('checkout_ip_hash', '127.0.0.1')->count());
        $this->assertSame(CheckoutService::ipHash('127.0.0.1'), Order::first()->checkout_ip_hash);

        // La fenêtre est d'une heure
        $this->travel(61)->minutes();
        $this->guestPost($this->cart($type, 1), ['email' => 'a5@example.test']);
        $this->assertSame(4, Order::count());
    }

    public function test_free_orders_per_email_are_capped_including_aliases(): void
    {
        config(['payments.free_orders_per_email_per_day' => 2, 'payments.free_orders_per_ip_per_hour' => 100]);
        $type = $this->freeType();

        $this->guestPost($this->cart($type, 1), ['email' => 'Meme@example.test'], '10.0.0.1');
        $this->guestPost($this->cart($type, 1), ['email' => 'meme+tag@example.test'], '10.0.0.2');
        $r = $this->guestPost($this->cart($type, 1), ['email' => 'meme@example.test'], '10.0.0.3');

        $r->assertSessionHas('error', CheckoutService::FREE_LIMIT_ERROR);
        $this->assertSame(2, Order::count());
        $this->assertSame(2, (int) $type->fresh()->quantity_sold);

        // Un autre email n'est pas touché ; le lendemain le quota est rendu
        $this->guestPost($this->cart($type, 1), ['email' => 'autre@example.test'], '10.0.0.4');
        $this->assertSame(3, Order::count());
        $this->travel(25)->hours();
        $this->guestPost($this->cart($type, 1), ['email' => 'meme@example.test'], '10.0.0.5');
        $this->assertSame(4, Order::count());
    }

    public function test_free_cap_applies_to_connected_users_too(): void
    {
        config(['payments.free_orders_per_email_per_day' => 1]);
        $type = $this->freeType();
        $user = User::find(1);

        $this->userPost($user, $this->cart($type, 1));
        $r = $this->userPost($user, $this->cart($type, 1));

        $r->assertSessionHas('error', CheckoutService::FREE_LIMIT_ERROR);
        $this->assertSame(1, Order::count());
    }

    public function test_free_orders_are_not_limited_by_pending_caps_and_do_not_use_them(): void
    {
        config(['payments.max_pending_orders' => 1, 'payments.max_pending_orders_per_ip' => 1, 'payments.max_pending_tickets_per_event' => 1, 'payments.pending_min' => 1]);
        $type = $this->freeType();

        foreach (['p1', 'p2', 'p3'] as $i => $name) {
            $this->guestPost($this->cart($type, 1), ['email' => $name . '@example.test'], '10.0.1.' . $i);
        }

        $this->assertSame(3, Order::where('payment_status', 'paid')->count());
        $this->assertSame(0, \Illuminate\Support\Facades\RateLimiter::attempts('guest-orders:' . sha1(CheckoutService::normalizeIp('10.0.1.0'))));
    }

    public function test_free_tickets_per_order_inherit_the_order_cap(): void
    {
        config(['payments.max_pending_tickets_per_buyer' => 2]);
        $type = $this->freeType();
        $type->update(['max_per_order' => 100]);

        $r = $this->guestPost($this->cart($type, 3));

        $r->assertSessionHas('error', CheckoutService::perOrderLimitMessage(2));
        $this->assertSame(0, Order::count());
        $this->assertSame(0, (int) $type->fresh()->quantity_sold);
    }

    public function test_cap_refusals_are_logged_without_personal_data(): void
    {
        config(['payments.free_orders_per_email_per_day' => 1]);
        $type = $this->freeType();
        $logged = [];
        Log::listen(function ($e) use (&$logged) {
            $logged[] = $e->message . json_encode($e->context);
        });

        $this->guestPost($this->cart($type, 1), ['email' => 'pii-test@example.test'], '10.9.9.1');
        $this->guestPost($this->cart($type, 1), ['email' => 'pii-test@example.test'], '10.9.9.2');

        $all = implode("\n", $logged);
        $this->assertStringContainsString('Plafond de commandes gratuites par email atteint', $all);
        $this->assertStringNotContainsString('pii-test', $all);
        $this->assertStringNotContainsString('10.9.9', $all);
        $this->assertStringNotContainsString('0102030405', $all);
    }

    public function test_free_order_locks_are_released_after_checkout(): void
    {
        $type = $this->freeType();
        $this->guestPost($this->cart($type, 1), ['email' => 'lock@example.test']);

        $service = app(CheckoutService::class);
        $groups = $service->buildEventGroups($this->cart($type, 1));
        $this->assertSame('ok', $service->withFreeOrderLocks($groups, 'lock@example.test', '127.0.0.1', fn () => 'ok'));
        $this->assertTrue(Cache::lock('free-orders-lock:email:' . sha1('lock@example.test'), 5)->get(), 'verrou email libéré');
    }

    // ===== Stock, concurrence, idempotence, robustesse =====

    public function test_last_free_ticket_has_a_single_winner(): void
    {
        $type = $this->freeType(1);

        $this->guestPost($this->cart($type, 1), ['email' => 'w1@example.test'], '10.0.2.1');
        $loser = $this->guestPost($this->cart($type, 1), ['email' => 'w2@example.test'], '10.0.2.2');

        $loser->assertSessionHas('error');
        $this->assertSame(1, Order::count());
        $this->assertSame(1, Order::where('payment_status', 'paid')->count());
        $this->assertSame(1, (int) $type->fresh()->quantity_sold);
        $this->assertSame(1, Ticket::count());
    }

    public function test_confirm_payment_is_idempotent_on_a_free_order(): void
    {
        $type = $this->freeType();
        $this->guestPost($this->cart($type, 2));
        $order = Order::firstOrFail();

        $service = app(OrderPaymentService::class);
        $service->confirmPayment($order, CheckoutService::FREE_REFERENCE);
        $service->confirmPayment($order, 'AUTRE');

        $order->refresh();
        $this->assertSame(CheckoutService::FREE_REFERENCE, $order->payment_reference);
        $this->assertSame(1, Payment::where('order_id', $order->id)->count());
        $this->assertSame(0, Commission::count());
        $this->assertSame(2, (int) $type->fresh()->quantity_sold);
    }

    public function test_email_failure_does_not_cancel_the_free_order(): void
    {
        $type = $this->freeType();
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp indisponible'));

        $r = $this->guestPost($this->cart($type, 1));

        $order = Order::firstOrFail();
        $r->assertRedirect(route('checkout.guest.confirmation', $order->guest_token));
        $this->assertConfirmedFree($order, 1);
        $this->assertSame(1, (int) $type->fresh()->quantity_sold);
    }

    public function test_failed_confirmation_releases_the_free_order_and_its_stock(): void
    {
        $type = $this->freeType();
        $this->partialMock(OrderPaymentService::class, function ($mock) {
            $mock->shouldReceive('confirmPayment')->andThrow(new \RuntimeException('panne'));
        });

        $r = $this->guestPost($this->cart($type, 2));

        $r->assertSessionHas('error', CheckoutService::FREE_FAILURE_ERROR);
        $order = Order::firstOrFail();
        $this->assertSame('failed', $order->payment_status, 'jamais de commande gratuite en suspens');
        $this->assertSame(0, (int) $type->fresh()->quantity_sold, 'stock rendu');
        $this->assertSame(0, Ticket::where('status', 'sold')->count());
    }

    public function test_expiry_command_never_touches_paid_free_orders(): void
    {
        $type = $this->freeType();
        $this->guestPost($this->cart($type, 1));

        $this->travel(5)->days();
        $this->artisan('orders:release-expired')->assertSuccessful();

        $this->assertSame('paid', Order::firstOrFail()->payment_status);
        $this->assertSame(1, (int) $type->fresh()->quantity_sold);
    }

    // ===== Vues =====

    public function test_free_checkout_pages_hide_payment_choice_and_show_free_wording(): void
    {
        $type = $this->freeType();
        $user = User::find(1);

        foreach ([
            $this->actingAs($user)->withSession(['cart' => $this->cart($type, 1, 500)])->get(route('checkout.show')),
        ] as $page) {
            $page->assertOk()->assertSee('Réserver gratuitement')->assertSee('Gratuit')
                ->assertSee('Vos billets sont émis immédiatement après validation')
                ->assertDontSee('name="payment_method"', false)->assertDontSee('Après validation, vos places sont réservées')
                ->assertDontSee('Valider la commande');
        }

        auth()->logout();
        $this->withSession(['cart' => $this->cart($type, 1, 500)])->get(route('checkout.guest.show'))
            ->assertOk()->assertSee('Réserver gratuitement')->assertDontSee('name="payment_method"', false)
            ->assertDontSee('Après validation, vos places sont réservées');
    }

    public function test_paid_checkout_pages_keep_payment_choice_even_if_session_says_free(): void
    {
        $paid = $this->freeType(10, 1000);

        // La session annonce 0 FCFA : le total affiché est recalculé côté serveur
        $this->withSession(['cart' => $this->cart($paid, 1, 0)])->get(route('checkout.guest.show'))
            ->assertOk()->assertSee('name="payment_method"', false)->assertSee('Valider la commande')
            ->assertDontSee('Réserver gratuitement');
    }

    public function test_buyer_order_detail_shows_free_order_as_confirmed(): void
    {
        $type = $this->freeType();
        $user = User::find(1);
        $this->userPost($user, $this->cart($type, 1));
        $order = Order::firstOrFail();

        $this->actingAs($user)->get(route('acheteur.orders.show', $order))
            ->assertOk()->assertSee('Confirmée (gratuite)')->assertSee('Gratuit (aucun paiement)')
            ->assertDontSee('En attente de paiement')->assertDontSee('MODE DÉMO');
    }

    public function test_free_confirmation_email_mentions_free_order_and_no_fee(): void
    {
        $type = $this->freeType();
        $this->guestPost($this->cart($type, 1));
        $order = Order::with(['event', 'tickets', 'user'])->firstOrFail();

        $html = view('emails.payment-confirmation', ['order' => $order])->render();

        $this->assertStringContainsString('Commande gratuite confirmée', $html);
        $this->assertStringNotContainsString('Paiement confirmé', $html);
        $this->assertStringNotContainsStringIgnoringCase('frais', $html);
    }

    // ===== Multi-événements, tout-ou-rien =====

    /** Type de billet rattaché à un 2e événement. */
    private function typeOnSecondEvent(int $price): TicketType
    {
        DB::table('events')->insert(['id' => 2, 'promoter_id' => 2, 'title' => 'Atelier', 'venue' => 'Salle', 'event_date' => '2027-02-01', 'event_time' => '10:00']);
        $type = $this->freeType(50, $price);
        $type->update(['event_id' => 2]);

        return $type->fresh();
    }

    private function mixedCart(): array
    {
        $free = $this->freeType();
        $paid = $this->typeOnSecondEvent(1000);

        return [$free, $paid, ['a' => $this->cart($free, 1)['k'], 'b' => $this->cart($paid, 1)['k']]];
    }

    public function test_two_free_events_create_two_confirmed_orders(): void
    {
        $a = $this->freeType();
        $b = $this->typeOnSecondEvent(0);

        $this->guestPost(['a' => $this->cart($a, 1)['k'], 'b' => $this->cart($b, 2)['k']]);

        $this->assertSame(2, Order::where('payment_status', 'paid')->count());
        $this->assertSame(0, Commission::count());
        $this->assertSame(1, (int) $a->fresh()->quantity_sold);
        $this->assertSame(2, (int) $b->fresh()->quantity_sold);
    }

    public function test_mixed_cart_without_payment_method_is_refused(): void
    {
        [$free, $paid, $cart] = $this->mixedCart();

        $this->guestPost($cart)->assertSessionHasErrors('payment_method');

        $this->assertSame(0, Order::count());
        $this->assertSame(0, (int) $free->fresh()->quantity_sold);
        $this->assertSame(0, (int) $paid->fresh()->quantity_sold);
    }

    public function test_mixed_cart_with_payment_method_confirms_free_and_leaves_paid_pending(): void
    {
        [$free, $paid, $cart] = $this->mixedCart();

        $r = $this->guestPost($cart, ['payment_method' => 'mobile_money']);

        $freeOrder = Order::where('event_id', 1)->firstOrFail();
        $paidOrder = Order::where('event_id', 2)->firstOrFail();
        $this->assertConfirmedFree($freeOrder, 1);
        $this->assertSame('pending', $paidOrder->payment_status);
        $this->assertSame('mobile_money', $paidOrder->payment_method);
        $this->assertSame(1000, (int) $paidOrder->total_amount);
        $this->assertSame(1, Commission::where('order_id', $paidOrder->id)->count());

        // Redirection vers la commande EN ATTENTE (pas la gratuite), message sans contradiction ni promesse d'email
        $r->assertRedirect(route('checkout.guest.confirmation', $paidOrder->guest_token));
        $r->assertSessionHas('success', 'Vos billets gratuits sont émis ; réglez la commande payante ci-dessous.');
        $this->assertStringNotContainsString('e-mail', (string) session('success'));
        $this->assertStringNotContainsString('email', (string) session('success'));

        // La page de la commande payante signale la commande gratuite déjà émise, avec un lien vers elle
        $this->get(route('checkout.guest.confirmation', $paidOrder->guest_token))
            ->assertOk()->assertSee('Votre commande gratuite #' . $freeOrder->order_number)
            ->assertSee(route('checkout.guest.confirmation', $freeOrder->guest_token), false);
    }

    public function test_mixed_connected_cart_redirects_to_the_pending_order(): void
    {
        [$free, $paid, $cart] = $this->mixedCart();
        // L'ordre du panier met la commande payante en second (tri par événement) : la cible est tout de même la pendante
        $this->userPost(User::find(1), $cart, ['payment_method' => 'cash'])
            ->assertRedirect(route('checkout.confirmation', Order::where('event_id', 2)->firstOrFail()));
    }

    public function test_requires_payment_method_uses_contains_not_every(): void
    {
        $service = app(CheckoutService::class);
        $this->assertTrue($service->requiresPaymentMethod([['subtotal' => 0], ['subtotal' => 1000]]));
        $this->assertTrue($service->requiresPaymentMethod([['subtotal' => 1000], ['subtotal' => 0]]));
        $this->assertFalse($service->requiresPaymentMethod([['subtotal' => 0], ['subtotal' => 0]]));
    }

    public function test_free_confirmation_failure_in_a_mixed_cart_releases_everything_then_a_retry_succeeds(): void
    {
        [$free, $paid, $cart] = $this->mixedCart();
        $real = new OrderPaymentService();
        $failNext = true;
        $this->partialMock(OrderPaymentService::class, function ($mock) use (&$failNext, $real) {
            $mock->shouldReceive('confirmPayment')->andReturnUsing(function (...$args) use (&$failNext, $real) {
                if ($failNext) {
                    throw new \RuntimeException('panne');
                }

                return $real->confirmPayment(...$args);
            });
        });

        $r = $this->guestPost($cart, ['payment_method' => 'cash']);

        $r->assertSessionHas('error', CheckoutService::FREE_FAILURE_ERROR);
        $this->assertSame(0, Order::whereIn('payment_status', ['pending', 'processing', 'paid'])->count(), 'aucune commande en suspens');
        $this->assertSame(0, (int) $free->fresh()->quantity_sold);
        $this->assertSame(0, (int) $paid->fresh()->quantity_sold, 'stock de la commande payante rendu');
        $this->assertSame(0, Ticket::whereIn('status', ['reserved', 'sold'])->count());
        $this->assertNotNull(session('cart'), 'panier conservé');

        // Renvoi : la panne est résolue, une seule paire de commandes (pas de doublon)
        $failNext = false;
        $this->guestPost($cart, ['payment_method' => 'cash'], '10.5.5.5');
        $this->assertSame(1, Order::where('payment_status', 'paid')->count());
        $this->assertSame(1, Order::where('payment_status', 'pending')->count());
        $this->assertSame(1, (int) $free->fresh()->quantity_sold);
        $this->assertSame(1, (int) $paid->fresh()->quantity_sold);
    }

    // ===== Plafonds complémentaires (M1) =====

    public function test_free_tickets_per_event_per_ip_are_capped(): void
    {
        config(['payments.free_tickets_per_event_per_ip_per_day' => 3, 'payments.free_orders_per_ip_per_hour' => 100, 'payments.free_orders_per_email_per_day' => 100]);
        $type = $this->freeType();

        $this->guestPost($this->cart($type, 2), ['email' => 'e1@example.test']);
        $this->guestPost($this->cart($type, 1), ['email' => 'e2@example.test']);
        $r = $this->guestPost($this->cart($type, 1), ['email' => 'e3@example.test']);

        $r->assertSessionHas('error', CheckoutService::FREE_LIMIT_ERROR);
        $this->assertSame(3, (int) $type->fresh()->quantity_sold, 'aucun stock réservé au-delà du plafond');

        // Autre IP : non limitée
        $this->guestPost($this->cart($type, 1), ['email' => 'e4@example.test'], '10.7.7.7');
        $this->assertSame(4, (int) $type->fresh()->quantity_sold);
    }

    public function test_cancelled_free_orders_do_not_count_toward_the_event_ip_ticket_cap(): void
    {
        config(['payments.free_tickets_per_event_per_ip_per_day' => 2, 'payments.free_orders_per_ip_per_hour' => 100, 'payments.free_orders_per_email_per_day' => 100]);
        $type = $this->freeType();

        $this->guestPost($this->cart($type, 2), ['email' => 'c1@example.test']);
        app(OrderPaymentService::class)->refund(Order::firstOrFail());

        $this->guestPost($this->cart($type, 2), ['email' => 'c2@example.test']);
        $this->assertSame(1, Order::where('payment_status', 'paid')->count());
    }

    public function test_free_orders_per_user_are_capped_even_when_the_email_changes(): void
    {
        config(['payments.free_orders_per_user_per_day' => 2, 'payments.free_orders_per_email_per_day' => 100, 'payments.free_orders_per_ip_per_hour' => 100, 'payments.free_tickets_per_event_per_ip_per_day' => 100]);
        $type = $this->freeType();
        $user = User::find(1);

        $this->userPost($user, $this->cart($type, 1), ['billing_email' => 'x1@example.test']);
        $this->userPost($user, $this->cart($type, 1), ['billing_email' => 'x2@example.test']);
        $r = $this->userPost($user, $this->cart($type, 1), ['billing_email' => 'x3@example.test']);

        $r->assertSessionHas('error', CheckoutService::FREE_LIMIT_ERROR);
        $this->assertSame(2, Order::count());
        $this->assertSame(2, (int) $type->fresh()->quantity_sold);
    }
}
