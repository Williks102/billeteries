<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Services\CheckoutService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/** Correctifs sécurité du paiement manuel : E1 (comptes invités), E2 (anti-accaparement), M3, CGU. */
class GuestCheckoutSecurityTest extends TestCase
{
    use BuildsOrderSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        Mail::fake();
    }

    private function payload(array $o = []): array
    {
        return $o + [
            'email' => 'invite@example.test', 'first_name' => 'Awa', 'last_name' => 'Kone',
            'phone' => '0102030405', 'terms_accepted' => '1', 'payment_method' => 'cash',
        ];
    }

    private function guestPost($type, array $payload, int $qty = 1)
    {
        return $this->withSession(['cart' => $this->tamperedCart($type, $qty)])
            ->post(route('checkout.guest.process'), $payload);
    }

    private function insertUser(int $id, string $email, bool $guest): void
    {
        DB::table('users')->insert([
            'id' => $id, 'name' => 'Nom Secret', 'first_name' => 'Nom', 'last_name' => 'Secret', 'email' => $email,
            'phone' => '0707070707', 'role' => 'acheteur', 'is_guest' => $guest,
            'password' => Hash::make('mot-de-passe-victime'),
        ]);
    }

    // ===== E1 =====

    public function test_existing_guest_is_never_converted_modified_or_logged_in_by_an_anonymous_checkout(): void
    {
        $type = $this->ticketType(10);
        $this->insertUser(40, 'victime@example.test', true);
        $passwordBefore = User::find(40)->password;

        $response = $this->guestPost($type, $this->payload([
            'email' => 'victime@example.test', 'first_name' => 'Pirate', 'last_name' => 'X', 'phone' => '0999999999',
            'create_account' => '1', 'password' => 'pirate123', 'password_confirmation' => 'pirate123',
        ]));

        $response->assertRedirect();
        $this->assertGuest();
        $victim = User::find(40);
        $this->assertTrue((bool) $victim->is_guest);
        $this->assertNull($victim->guest_converted_at);
        $this->assertSame('Nom Secret', $victim->name);
        $this->assertSame('Nom', $victim->first_name);
        $this->assertSame('0707070707', $victim->phone);
        $this->assertSame($passwordBefore, $victim->password);
        $this->assertTrue(Hash::check('mot-de-passe-victime', $victim->password));
        $this->assertSame(1, Order::count()); // la commande est bien créée, rattachée sans toucher au compte
    }

    public function test_existing_normal_account_is_untouched_and_not_logged_in(): void
    {
        $type = $this->ticketType(10);
        $this->insertUser(41, 'normal@example.test', false);

        $this->guestPost($type, $this->payload(['email' => 'normal@example.test', 'create_account' => '1', 'password' => 'x12345', 'password_confirmation' => 'x12345']))
            ->assertRedirect();

        $this->assertGuest();
        $user = User::find(41);
        $this->assertFalse((bool) $user->is_guest);
        $this->assertSame('Nom Secret', $user->name);
        $this->assertSame('0707070707', $user->phone);
        $this->assertTrue(Hash::check('mot-de-passe-victime', $user->password));
    }

    public function test_response_is_identical_whether_the_email_is_known_or_not(): void
    {
        $type = $this->ticketType(10);
        $this->insertUser(42, 'connu@example.test', false);

        $known = $this->guestPost($type, $this->payload(['email' => 'connu@example.test']));
        $knownLocation = $known->headers->get('Location');
        $knownFlash = session('success');
        $knownErrors = session('errors');

        $this->flushSession();
        $unknown = $this->guestPost($type, $this->payload(['email' => 'inconnu@example.test']));
        $unknownLocation = $unknown->headers->get('Location');

        $this->assertSame($known->getStatusCode(), $unknown->getStatusCode());
        $this->assertSame(
            preg_replace('~/[A-Za-z0-9]{32}$~', '/TOKEN', $knownLocation),
            preg_replace('~/[A-Za-z0-9]{32}$~', '/TOKEN', $unknownLocation)
        );
        $this->assertSame($knownFlash, session('success'));
        $this->assertNull($knownErrors);
        $this->assertNull(session('errors'));
        $this->assertSame(2, Order::whereNotNull('guest_token')->count());
    }

    public function test_account_creation_after_purchase_converts_with_unverified_email(): void
    {
        $type = $this->ticketType(10);
        $this->guestPost($type, $this->payload())->assertRedirect();
        $order = Order::sole();
        $user = User::where('email', 'invite@example.test')->first();
        $this->assertTrue((bool) $user->is_guest);
        $user->forceFill(['email_verified_at' => now()])->save(); // même si un état « vérifié » existait, il est remis à null

        $this->post(route('checkout.guest.create-account', $order->guest_token), [
            'password' => 'secret123', 'password_confirmation' => 'secret123',
        ])->assertRedirect(route('acheteur.dashboard'));

        $user->refresh();
        $this->assertFalse((bool) $user->is_guest);
        $this->assertNotNull($user->guest_converted_at);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue(Hash::check('secret123', $user->password));
        $this->assertAuthenticatedAs($user);
        $this->assertNull($order->fresh()->guest_token);
    }

    public function test_attacker_with_own_session_proof_cannot_convert_victim_guest_account(): void
    {
        $type = $this->ticketType(10);
        // Victime : compte invité issu de SA commande (la plus ancienne du compte)
        $this->guestPost($type, $this->payload(['email' => 'victime@example.test']))->assertRedirect();
        $this->flushSession();
        $victim = User::where('email', 'victime@example.test')->first();
        $passwordBefore = $victim->password;

        // Attaquant : nouvelle session, commande avec l'email de la victime => preuve de session valide sur SA commande
        $this->guestPost($type, $this->payload(['email' => 'victime@example.test', 'first_name' => 'Pirate']))->assertRedirect();
        $attackerOrder = Order::orderByDesc('id')->first();
        $this->assertSame($attackerOrder->guest_token, session('guest_order_tokens.' . $attackerOrder->id));

        $this->post(route('checkout.guest.create-account', $attackerOrder->guest_token), [
            'password' => 'pirate123', 'password_confirmation' => 'pirate123',
        ])->assertSessionHasErrors('error');

        $this->assertGuest();
        $victim->refresh();
        $this->assertTrue((bool) $victim->is_guest);
        $this->assertSame($passwordBefore, $victim->password);
    }

    public function test_account_creation_is_refused_for_an_existing_normal_account(): void
    {
        $type = $this->ticketType(10);
        $this->insertUser(43, 'normal@example.test', false);
        $this->guestPost($type, $this->payload(['email' => 'normal@example.test']))->assertRedirect();
        $order = Order::sole();

        $this->post(route('checkout.guest.create-account', $order->guest_token), [
            'password' => 'pirate123', 'password_confirmation' => 'pirate123',
        ])->assertSessionHasErrors('error');

        $this->assertGuest();
        $this->assertTrue(Hash::check('mot-de-passe-victime', User::find(43)->password));
    }

    public function test_guest_checkout_logs_do_not_contain_the_email(): void
    {
        $type = $this->ticketType(10);
        Log::spy();

        $this->guestPost($type, $this->payload(['email' => 'secret.pii@example.test']))->assertRedirect();

        Log::shouldNotHaveReceived('info', fn ($m, $ctx = []) => str_contains(json_encode([$m, $ctx]), 'secret.pii@example.test'));
        $this->assertTrue(true);
    }

    // ===== CGU =====

    public function test_guest_checkout_must_refuse_terms_explicitly_declined(): void
    {
        $type = $this->ticketType(10);

        foreach (['0', 'false', 'off', ''] as $value) {
            $this->guestPost($type, $this->payload(['terms_accepted' => $value]))->assertSessionHasErrors('terms_accepted');
        }

        $this->assertSame(0, Order::count());
    }

    public function test_authenticated_checkout_must_refuse_terms_explicitly_declined(): void
    {
        $type = $this->ticketType(10);

        $this->actingAs(User::find(1))->withSession(['cart' => $this->tamperedCart($type, 1)])
            ->post(route('checkout.process'), [
                'billing_email' => 'a@b.test', 'billing_phone' => '0102030405', 'terms_accepted' => '0', 'payment_method' => 'cash',
            ])->assertSessionHasErrors('terms_accepted');

        $this->assertSame(0, Order::count());
    }

    // ===== E2 =====

    public function test_eleventh_guest_checkout_post_in_a_minute_is_throttled(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $this->post(route('checkout.guest.process'), [])->assertStatus(302);
        }

        $this->post(route('checkout.guest.process'), [])->assertStatus(429);
    }

    public function test_eleventh_authenticated_checkout_post_in_a_minute_is_throttled(): void
    {
        $this->actingAs(User::find(1));

        for ($i = 1; $i <= 10; $i++) {
            $this->post(route('checkout.process'), [])->assertStatus(302);
        }

        $this->post(route('checkout.process'), [])->assertStatus(429);
    }

    public function test_pending_orders_per_email_are_capped_with_a_generic_message(): void
    {
        $type = $this->ticketType(20);

        for ($i = 1; $i <= 3; $i++) {
            $this->guestPost($type, $this->payload())->assertRedirect();
        }
        $this->assertSame(3, $type->fresh()->quantity_sold);

        $this->guestPost($type, $this->payload())->assertSessionHas('error', CheckoutService::LIMIT_ERROR);

        $this->assertSame(3, Order::count());
        $this->assertSame(3, $type->fresh()->quantity_sold);

        // Une commande expirée/libérée libère le plafond
        Order::first()->releaseStock();
        $this->guestPost($type, $this->payload())->assertRedirect();
        $this->assertSame(4, Order::count());
    }

    public function test_pending_orders_per_ip_are_capped_across_emails(): void
    {
        config(['payments.max_pending_orders_per_ip' => 2]);
        $type = $this->ticketType(20);

        $this->guestPost($type, $this->payload(['email' => 'a@example.test']))->assertRedirect();
        $this->guestPost($type, $this->payload(['email' => 'b@example.test']))->assertRedirect();
        $this->guestPost($type, $this->payload(['email' => 'c@example.test']))->assertSessionHas('error', CheckoutService::LIMIT_ERROR);

        $this->assertSame(2, Order::count());
    }

    public function test_authenticated_pending_orders_are_capped_per_user(): void
    {
        $type = $this->ticketType(20);
        $user = User::find(1);

        for ($i = 1; $i <= 3; $i++) {
            $this->actingAs($user)->withSession(['cart' => $this->tamperedCart($type, 1)])
                ->post(route('checkout.process'), ['billing_email' => "x$i@example.test", 'billing_phone' => '0102030405', 'terms_accepted' => '1', 'payment_method' => 'cash'])
                ->assertRedirect();
        }

        $this->actingAs($user)->withSession(['cart' => $this->tamperedCart($type, 1)])
            ->post(route('checkout.process'), ['billing_email' => 'x9@example.test', 'billing_phone' => '0102030405', 'terms_accepted' => '1', 'payment_method' => 'cash'])
            ->assertSessionHas('error', CheckoutService::LIMIT_ERROR);

        $this->assertSame(3, Order::count());
    }

    public function test_reservation_is_24h_for_guests_and_48h_for_logged_in_buyers(): void
    {
        $type = $this->ticketType(20);

        $this->guestPost($type, $this->payload())->assertRedirect();
        $this->actingAs(User::find(1))->withSession(['cart' => $this->tamperedCart($type, 1)])
            ->post(route('checkout.process'), ['billing_email' => 'a@example.test', 'billing_phone' => '0102030405', 'terms_accepted' => '1', 'payment_method' => 'cash'])
            ->assertRedirect();

        [$guest, $logged] = [Order::whereNotNull('guest_token')->first(), Order::whereNull('guest_token')->first()];
        $this->assertEqualsWithDelta(now()->addMinutes(1440)->timestamp, $guest->expires_at->timestamp, 5);
        $this->assertEqualsWithDelta(now()->addMinutes(2880)->timestamp, $logged->expires_at->timestamp, 5);
    }

    // ===== M3 =====

    public function test_internal_errors_return_a_generic_message_and_log_without_raw_message(): void
    {
        Log::spy();
        $cart = ['k' => ['ticket_type_id' => 987654, 'event_id' => 1, 'quantity' => 1, 'ticket_name' => 'X', 'unit_price' => 1, 'total_price' => 1]];

        $guest = $this->withSession(['cart' => $cart])->post(route('checkout.guest.process'), $this->payload());
        $guest->assertSessionHas('error', CheckoutService::GENERIC_ERROR);

        $auth = $this->actingAs(User::find(1))->withSession(['cart' => $cart])->post(route('checkout.process'), [
            'billing_email' => 'a@b.test', 'billing_phone' => '0102030405', 'terms_accepted' => '1', 'payment_method' => 'cash',
        ]);
        $auth->assertSessionHas('error', CheckoutService::GENERIC_ERROR);

        foreach ([session('error'), $guest->getContent(), $auth->getContent()] as $out) {
            $this->assertStringNotContainsString('TicketType', (string) $out);
            $this->assertStringNotContainsString('No query results', (string) $out);
        }

        Log::shouldHaveReceived('error')->withArgs(fn ($m, $ctx = []) => isset($ctx['exception']) && !array_key_exists('error', $ctx))->twice();
    }

    public function test_stock_unavailable_message_stays_explicit_for_the_buyer(): void
    {
        $type = $this->ticketType(1);

        $this->guestPost($type, $this->payload(), 2)
            ->assertSessionHas('error', fn ($m) => str_contains($m, "n'est plus disponible"));
        $this->assertSame(0, Order::count());
        $this->assertSame(0, $type->fresh()->quantity_sold);
    }
}
