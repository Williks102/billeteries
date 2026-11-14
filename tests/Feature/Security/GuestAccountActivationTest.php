<?php

namespace Tests\Feature\Security;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\ActivateGuestAccount;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/**
 * Achat invité puis inscription avec le même e-mail : AUCUNE reprise de la ligne invitée sans preuve de possession
 * de l'adresse (lien envoyé par e-mail). Les commandes ne sont visibles qu'après activation par ce lien.
 */
class GuestAccountActivationTest extends TestCase
{
    use BuildsOrderSchema;

    private const GUEST = 'invite@example.test';
    private const PNG = 'data:image/png;base64,QUJDREVGRw==';

    private string $unusableHash;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        Schema::create('password_reset_tokens', function ($t) {
            $t->string('email')->primary();
            $t->string('token');
            $t->timestamp('created_at')->nullable();
        });
        View::share('demoMode', false);
        config(['mail.default' => 'smtp']);
        Notification::fake();

        $this->unusableHash = '$2y$12$'.str_repeat('a', 53);
        DB::table('users')->insert([
            'id' => 20, 'name' => 'Awa Invitée', 'email' => self::GUEST, 'phone' => '+2250700000000',
            'password' => $this->unusableHash, 'role' => 'acheteur', 'is_guest' => true,
        ]);
        DB::table('users')->insert([
            'id' => 21, 'name' => 'Vrai Compte', 'email' => 'vrai@example.test', 'role' => 'acheteur',
            'password' => bcrypt('Mot-de-passe-123'), 'is_guest' => false,
        ]);
        $this->paidOrderAndPendingOrder();
    }

    /** Une commande payée (billet émis + QR) et une commande en attente, toutes deux reliées à la ligne invitée. */
    private function paidOrderAndPendingOrder(): void
    {
        $type = $this->ticketType(10);
        $paid = $this->reservedOrder($type, 1, 'pending', now()->addHours(5));
        $paid->update(['user_id' => 20, 'payment_status' => 'paid', 'paid_at' => now()]);
        $t = Ticket::first();
        $t->update(['order_item_id' => $paid->orderItems()->first()->id, 'status' => 'sold', 'qr_code' => self::PNG]);

        $pending = $this->reservedOrder($type, 1, 'pending', now()->addHours(5));
        $pending->update(['user_id' => 20]);
    }

    private function register(string $email, string $ip = '10.0.0.1', string $password = 'Pirate-mdp-123')
    {
        $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])->from('/register')->post(route('register'), [
            'name' => 'Quelqu\'un', 'email' => $email, 'phone' => '07 12 34 56 78', 'role' => 'acheteur',
            'password' => $password, 'password_confirmation' => $password,
        ]);
        $this->forgetTerminatingCallbacks();

        return $response;
    }

    /** Artefact de test : l'application est réutilisée, alors qu'en production chaque requête a son processus. */
    private function forgetTerminatingCallbacks(): void
    {
        $p = new \ReflectionProperty($this->app, 'terminatingCallbacks');
        $p->setAccessible(true);
        $p->setValue($this->app, []);
    }

    private function guestRow(): object
    {
        return DB::table('users')->where('id', 20)->first();
    }

    private function activationUrlToken(): string
    {
        $notification = Notification::sent(User::find(20), ActivateGuestAccount::class)->first();
        $this->assertNotNull($notification);

        return $notification->token;
    }

    private function activate(string $token, string $email = self::GUEST, string $password = 'Nouveau-mdp-123', string $ip = '10.0.7.7')
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])->from('/password/reset/x')->post(route('password.update'), [
            'token' => $token, 'email' => $email, 'password' => $password, 'password_confirmation' => $password,
        ]);
    }

    // (a)
    public function test_register_with_a_guest_email_creates_nothing_changes_nothing_and_sends_the_activation(): void
    {
        $before = $this->guestRow();
        $users = DB::table('users')->count();

        $res = $this->register(self::GUEST);

        $res->assertRedirect(route('register'));
        $res->assertSessionHas('status', fn ($m) => str_contains($m, 'a déjà servi pour un achat sans compte') && str_contains($m, 'lien'));
        $res->assertSessionHasNoErrors();
        $this->assertGuest();
        $this->assertSame($users, DB::table('users')->count());
        $after = $this->guestRow();
        $this->assertEquals($before, $after);
        $this->assertSame($this->unusableHash, $after->password);
        $this->assertSame(2, DB::table('orders')->where('user_id', 20)->count());
        Notification::assertSentToTimes(User::find(20), ActivateGuestAccount::class, 1);
        Notification::assertNotSentTo(User::find(20), ResetPassword::class);
        $this->assertDatabaseCount('password_reset_tokens', 1);
    }

    public function test_demo_without_real_mail_says_so_instead_of_promising_an_email(): void
    {
        config(['mail.default' => 'log']);
        $this->register(self::GUEST)->assertSessionHas('status', fn ($m) => str_contains($m, "n'est pas activé sur cette démonstration"));
    }

    // (b)
    public function test_register_with_a_real_account_email_is_still_refused(): void
    {
        $users = DB::table('users')->count();
        $this->register('vrai@example.test')->assertSessionHasErrors(['email' => 'Cet email est déjà utilisé.']);
        $this->assertSame($users, DB::table('users')->count());
        Notification::assertNothingSent();
    }

    public function test_normal_registration_with_an_unknown_email_is_unchanged(): void
    {
        $this->register('nouveau@example.test')->assertRedirect(route('acheteur.dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'nouveau@example.test', 'is_guest' => false]);
        Notification::assertNotSentTo(User::where('email', 'nouveau@example.test')->first(), ActivateGuestAccount::class);
    }

    // (c)
    public function test_an_attacker_knowing_only_the_email_gets_neither_login_nor_orders(): void
    {
        $this->register(self::GUEST, '10.0.0.1', 'Pirate-mdp-123');

        $this->assertGuest();
        $this->from('/login')->post(route('login'), ['email' => self::GUEST, 'password' => 'Pirate-mdp-123']);
        $this->assertGuest();

        foreach (['acheteur.dashboard', 'acheteur.orders.index', 'acheteur.tickets.index'] as $name) {
            $res = $this->get(route($name));
            $this->assertContains($res->getStatusCode(), [302, 401, 403]);
            $this->assertStringNotContainsString('QUJDREVGRw', $res->getContent());
        }
        $this->assertSame($this->unusableHash, $this->guestRow()->password);
        $this->assertTrue((bool) $this->guestRow()->is_guest);
    }

    // (d)
    public function test_activation_by_the_link_converts_the_account_and_shows_previous_orders(): void
    {
        $this->register(self::GUEST);
        $token = $this->activationUrlToken();

        $this->activate($token)->assertRedirect(route('acheteur.dashboard'));

        $u = $this->guestRow();
        $this->assertFalse((bool) $u->is_guest);
        $this->assertNotNull($u->guest_converted_at);
        $this->assertNotNull($u->email_verified_at);
        $this->assertSame('acheteur', $u->role);
        $this->assertSame('Awa Invitée', $u->name);
        $this->assertSame('+2250700000000', $u->phone);
        $this->assertTrue(\Hash::check('Nouveau-mdp-123', $u->password));
        $this->assertAuthenticatedAs(User::find(20));

        $this->get(route('acheteur.dashboard'))->assertOk();
        $orders = $this->get(route('acheteur.orders.index'))->assertOk();
        $this->assertSame(2, $orders->viewData('orders')->total());
        $ticket = Ticket::first();
        $this->get(route('acheteur.tickets.show', $ticket))->assertOk()->assertSee('<img src="'.self::PNG, false);
    }

    // (e)
    public function test_used_expired_or_foreign_links_are_refused_and_convert_nothing(): void
    {
        $this->register(self::GUEST);
        $token = $this->activationUrlToken();

        // autre e-mail (celui d'un vrai compte)
        $this->activate($token, 'vrai@example.test', 'Autre-mdp-12345', '10.0.8.1')->assertSessionHasErrors('email');
        $this->assertTrue((bool) $this->guestRow()->is_guest);

        // jeton inventé
        $this->activate('faux-jeton', self::GUEST, 'Autre-mdp-12345', '10.0.8.2')->assertSessionHasErrors('email');
        $this->assertTrue((bool) $this->guestRow()->is_guest);

        // expiré
        DB::table('password_reset_tokens')->update(['created_at' => now()->subDays(2)]);
        $this->activate($token, self::GUEST, 'Autre-mdp-12345', '10.0.8.3')->assertSessionHasErrors('email');
        $this->assertTrue((bool) $this->guestRow()->is_guest);
        $this->assertSame($this->unusableHash, $this->guestRow()->password);
        $this->assertGuest();

        // valide une fois, refusé la seconde fois
        DB::table('password_reset_tokens')->truncate();
        $fresh = Password::broker()->createToken(User::find(20));
        $this->activate($fresh)->assertRedirect(route('acheteur.dashboard'));
        auth()->logout();
        $this->activate($fresh, self::GUEST, 'Rejoue-mdp-12345', '10.0.8.4')->assertSessionHasErrors('email');
        $this->assertTrue(\Hash::check('Nouveau-mdp-123', $this->guestRow()->password));
    }

    public function test_a_real_account_reset_is_unaffected(): void
    {
        $token = Password::broker()->createToken(User::find(21));
        DB::table('users')->where('id', 21)->update(['guest_converted_at' => null, 'email_verified_at' => null]);

        $this->activate($token, 'vrai@example.test')->assertRedirect(route('acheteur.dashboard'));

        $u = DB::table('users')->where('id', 21)->first();
        $this->assertNull($u->guest_converted_at);
        $this->assertNull($u->email_verified_at);
        $this->assertFalse((bool) $u->is_guest);
    }

    // (f)
    public function test_two_close_requests_send_one_email_with_identical_responses(): void
    {
        $first = $this->register(self::GUEST);
        $second = $this->register(self::GUEST);

        Notification::assertSentToTimes(User::find(20), ActivateGuestAccount::class, 1);
        $this->assertSame($first->headers->get('Location'), $second->headers->get('Location'));
        $this->assertSame($first->getSession()->get('status'), $second->getSession()->get('status'));
    }

    public function test_activation_requests_are_throttled_per_email_and_ip_silently(): void
    {
        config(['payments.password_reset_throttle_per_minute' => 100]);
        $responses = [];
        for ($i = 0; $i < 4; $i++) {
            DB::table('password_reset_tokens')->truncate(); // isole la limite (e-mail, IP) du délai du broker
            $responses[] = $this->register(self::GUEST, '10.0.3.3');
        }
        Notification::assertSentToTimes(User::find(20), ActivateGuestAccount::class, 3);
        $this->assertSame($responses[0]->getSession()->get('status'), $responses[3]->getSession()->get('status'));
        $this->assertSame($responses[0]->headers->get('Location'), $responses[3]->headers->get('Location'));

        // une autre IP n'est pas bloquée par le quota de la première
        DB::table('password_reset_tokens')->truncate();
        $this->register(self::GUEST, '10.0.3.4');
        Notification::assertSentToTimes(User::find(20), ActivateGuestAccount::class, 4);
    }

    // (g)
    public function test_the_email_is_sent_after_the_response(): void
    {
        $request = Request::create(route('register'), 'POST', [
            'name' => 'X', 'email' => self::GUEST, 'phone' => '0712345678', 'role' => 'acheteur',
            'password' => 'Pirate-mdp-123', 'password_confirmation' => 'Pirate-mdp-123',
        ]);
        $request->setLaravelSession($this->app['session.store']);

        $response = $this->app->make(\App\Http\Controllers\Auth\RegisterController::class)->register($request);

        $this->assertSame(302, $response->getStatusCode());
        Notification::assertNothingSent();
        $this->app->terminate();
        Notification::assertSentToTimes(User::find(20), ActivateGuestAccount::class, 1);
    }

    // (h)
    public function test_no_email_or_token_in_the_logs(): void
    {
        $logs = [];
        Log::listen(function ($m) use (&$logs) {
            $logs[] = $m->message.' '.json_encode($m->context);
        });

        $this->register(self::GUEST);
        $token = $this->activationUrlToken();
        $this->activate($token);

        $this->assertNotEmpty($logs);
        $all = implode("\n", $logs);
        $this->assertStringNotContainsString('invite@example', $all);
        $this->assertStringNotContainsString($token, $all);
        $this->assertStringNotContainsString('10.0.0.1', $all);
    }

    // (i)
    public function test_a_guest_clicking_forgot_password_gets_the_activation_text(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.4.4'])->post(route('password.email'), ['email' => self::GUEST]);
        Notification::assertSentToTimes(User::find(20), ActivateGuestAccount::class, 1);
        Notification::assertNotSentTo(User::find(20), ResetPassword::class);

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.4.5'])->post(route('password.email'), ['email' => 'vrai@example.test']);
        Notification::assertSentToTimes(User::find(21), ResetPassword::class, 1);
        Notification::assertNotSentTo(User::find(21), ActivateGuestAccount::class);
    }

    public function test_the_activation_mail_has_a_link_validity_and_no_order_data(): void
    {
        $user = User::find(20);
        $mail = (new ActivateGuestAccount('tok123'))->toMail($user);
        $this->assertSame('Activez votre compte pour retrouver vos billets', $mail->subject);
        $this->assertStringContainsString('/password/reset/tok123', $mail->actionUrl);
        $this->assertStringContainsString('email=', $mail->actionUrl);
        $body = implode(' ', array_map('strval', array_merge($mail->introLines, $mail->outroLines)));
        $this->assertStringContainsString('minutes', $body);
        $this->assertStringContainsString("ignorez ce message", $body);
        $this->assertStringNotContainsString('FCFA', $body);
    }

    // Point 5/6
    public function test_live_email_check_does_not_reveal_guest_purchases_and_page_has_help_text(): void
    {
        $guest = $this->postJson('/api/check-email', ['email' => self::GUEST])->assertOk();
        $unknown = $this->postJson('/api/check-email', ['email' => 'inconnu@example.test'])->assertOk();
        $this->assertSame($unknown->json(), $guest->json());
        $this->assertTrue($guest->json('available'));

        $page = $this->get(route('register'))->assertOk();
        $page->assertSee('Vous avez déjà acheté sans compte avec cet e-mail ?');
        $this->assertStringNotContainsString('frais de service', mb_strtolower($page->getContent()));
        $this->assertDoesNotMatchRegularExpression('/\son(click|input|change|submit)=/i', $page->getContent());
    }

    // ===== Lien construit sur APP_URL, jamais sur le Host de la requête (audit : empoisonnement de l'en-tête Host) =====

    public function test_activation_link_uses_app_url_even_when_the_request_host_is_forged(): void
    {
        config(['app.url' => 'https://billetterie.example']);

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.9.9'])
            ->call('POST', 'http://evil.example/register', [
                'name' => 'Pirate', 'email' => self::GUEST, 'phone' => '0712345678', 'role' => 'acheteur',
                'password' => 'Pirate-mdp-123', 'password_confirmation' => 'Pirate-mdp-123',
            ], [], [], ['HTTP_HOST' => 'evil.example', 'HTTP_X_FORWARDED_HOST' => 'evil.example']);
        $this->forgetTerminatingCallbacks();

        $notification = Notification::sent(User::find(20), ActivateGuestAccount::class)->first();
        $this->assertNotNull($notification);
        $url = $notification->toMail(User::find(20))->actionUrl;

        $this->assertStringStartsWith('https://billetterie.example/password/reset/', $url);
        $this->assertStringNotContainsString('evil.example', $url);
    }

    public function test_standard_password_reset_link_also_uses_app_url(): void
    {
        config(['app.url' => 'https://billetterie.example']);
        $user = User::find(21);

        $url = (new ResetPassword('un-jeton'))->toMail($user)->actionUrl;

        $this->assertStringStartsWith('https://billetterie.example/password/reset/un-jeton', $url);
        $this->assertStringContainsString('email=vrai%40example.test', $url);
    }

    public function test_the_link_helper_never_reads_the_request(): void
    {
        $this->app['request']->headers->set('HOST', 'evil.example');

        $this->assertStringNotContainsString('evil.example', \App\Support\AuthLinks::resetUrl('t', 'a@b.test'));
    }
}
