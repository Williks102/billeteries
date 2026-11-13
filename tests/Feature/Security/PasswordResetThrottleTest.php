<?php

namespace Tests\Feature\Security;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/** « Mot de passe oublié » et « réinitialisation » : limite par IP et par e-mail, réponse neutre, aucune donnée en clair. */
class PasswordResetThrottleTest extends TestCase
{
    use BuildsOrderSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        Schema::create('password_reset_tokens', function ($t) {
            $t->string('email')->primary();
            $t->string('token');
            $t->timestamp('created_at')->nullable();
        });
        $this->makeUser(3, 'acheteur'); // acheteur3@example.test
        Notification::fake();
    }

    private function forgot(string $email, string $ip = '10.0.0.1')
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])->from('/password/reset')->post(route('password.email'), ['email' => $email]);
    }

    public function test_response_is_identical_whether_the_account_exists_or_not(): void
    {
        $known = $this->forgot('acheteur3@example.test');
        $unknown = $this->forgot('inconnu@example.test');

        $this->assertSame($known->getStatusCode(), $unknown->getStatusCode());
        $this->assertSame($known->headers->get('Location'), $unknown->headers->get('Location'));
        $this->assertSame(session('status'), $known->getSession()->get('status'));
        $this->assertSame($known->getSession()->get('status'), $unknown->getSession()->get('status'));
        $this->assertNull($unknown->getSession()->get('errors'));
    }

    public function test_per_email_limit_gives_a_french_429_without_plain_keys(): void
    {
        $email = 'acheteur3@example.test';
        // 3 demandes depuis des IP différentes : seule la limite par e-mail joue
        $this->forgot($email, '10.0.0.1')->assertStatus(302);
        $this->forgot($email, '10.0.0.2')->assertStatus(302);
        $this->forgot(strtoupper($email), '10.0.0.3')->assertStatus(302); // casse normalisée

        $blocked = $this->forgot($email, '10.0.0.4');
        $blocked->assertStatus(429);
        $this->assertStringContainsString('Trop de tentatives', $blocked->getContent());

        // Même refus pour une adresse inexistante au-delà de 3 : le 429 ne révèle rien sur l'existence du compte
        foreach (['10.0.1.1', '10.0.1.2', '10.0.1.3'] as $ip) {
            $this->forgot('fantome@example.test', $ip)->assertStatus(302);
        }
        $this->forgot('fantome@example.test', '10.0.1.4')->assertStatus(429);

        // Aucune clé de cache ne contient l'e-mail ou l'IP en clair
        $store = Cache::getStore();
        $ref = new \ReflectionProperty($store, 'storage');
        $ref->setAccessible(true);
        foreach (array_keys($ref->getValue($store)) as $key) {
            $this->assertStringNotContainsString('acheteur3', (string) $key);
            $this->assertStringNotContainsString('fantome', (string) $key);
            $this->assertStringNotContainsString('10.0.', (string) $key);
        }
    }

    public function test_per_ip_limit(): void
    {
        config(['payments.password_reset_throttle_per_minute' => 5]);
        for ($i = 1; $i <= 5; $i++) {
            $this->forgot("u{$i}@example.test", '10.9.9.9')->assertStatus(302);
        }
        $this->forgot('u6@example.test', '10.9.9.9')->assertStatus(429);
        // une autre IP n'est pas affectée
        $this->forgot('u7@example.test', '10.9.9.10')->assertStatus(302);
    }

    public function test_reset_submission_is_throttled_and_failure_is_neutral(): void
    {
        $payload = fn (string $email) => [
            'token' => 'faux', 'email' => $email, 'password' => 'Nouveau-mdp-123', 'password_confirmation' => 'Nouveau-mdp-123',
        ];
        $post = fn (string $email, string $ip) => $this->withServerVariables(['REMOTE_ADDR' => $ip])->from('/password/reset/x')
            ->post(route('password.update'), $payload($email));

        $known = $post('acheteur3@example.test', '10.1.0.1');
        $unknown = $post('inconnu@example.test', '10.1.0.2');
        $this->assertSame(
            $known->getSession()->get('errors')->get('email'),
            $unknown->getSession()->get('errors')->get('email')
        );

        for ($i = 0; $i < 4; $i++) {
            $post('acheteur3@example.test', '10.2.0.'.$i)->assertStatus(302);
        }
        $post('acheteur3@example.test', '10.2.0.99')->assertStatus(429);
    }
}
