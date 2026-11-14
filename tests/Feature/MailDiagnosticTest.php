<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\MailDiagnostic;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/**
 * Le test d'envoi de l'admin doit dire CE QUI NE VA PAS (hôte oublié, mot de passe refusé…) sans jamais afficher le texte
 * de l'erreur ni un secret.
 */
class MailDiagnosticTest extends TestCase
{
    use BuildsOrderSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOrderSchema();
        View::share('demoMode', false);
        $this->makeUser(10, 'admin');
    }

    private function send()
    {
        return $this->actingAs(User::find(10))->postJson(route('admin.emails.test'), ['email' => 'a@b.test']);
    }

    private function smtp(array $override = []): void
    {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp' => array_merge([
            'transport' => 'smtp', 'host' => 'smtp.gmail.com', 'port' => 587, 'username' => 'compte@example.test', 'password' => 'MOTDEPASSEAPP',
        ], $override)]);
    }

    public function test_missing_host_is_reported_before_any_sending(): void
    {
        $this->smtp(['host' => '127.0.0.1']);
        Mail::shouldReceive('raw')->never();

        $r = $this->send();
        $r->assertStatus(422)->assertJson(['success' => false]);
        $this->assertStringContainsString('MAIL_HOST', $r->json('message'));
        $this->assertStringNotContainsString('127.0.0.1', $r->json('message').'') ;
    }

    public function test_missing_credentials_are_reported_by_name_only(): void
    {
        $this->smtp(['password' => '']);
        Mail::shouldReceive('raw')->never();

        $r = $this->send();
        $r->assertStatus(422);
        $this->assertStringContainsString('MAIL_PASSWORD', $r->json('message'));
        $this->assertStringNotContainsString('compte@example.test', $r->getContent());
    }

    public static function failures(): array
    {
        return [
            'mot de passe refusé' => ['Failed to authenticate on SMTP server with username "compte@example.test" using the following authenticators: LOGIN. Expected response code "235" but got code "535"', 'MAIL_PASSWORD'],
            'connexion impossible' => ['Connection could not be established with host "smtp.gmail.com:587": stream_socket_client(): Unable to connect', 'MAIL_PORT'],
            'TLS' => ['Unable to connect with STARTTLS: stream_socket_enable_crypto(): SSL operation failed with code 1', 'MAIL_SCHEME'],
            'expéditeur' => ['Expected response code "250" but got code "553", with message "553 5.1.8 sender address rejected"', 'MAIL_FROM_ADDRESS'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('failures')]
    public function test_each_failure_gets_a_french_hint_and_leaks_nothing(string $raw, string $expectedVariable): void
    {
        $this->smtp();
        Mail::shouldReceive('raw')->andThrow(new \RuntimeException($raw.' smtp://compte:MOTDEPASSEAPP@smtp.gmail.com'));
        Log::spy();

        $r = $this->send();
        $r->assertStatus(500)->assertJson(['success' => false]);
        $this->assertStringContainsString($expectedVariable, $r->json('message'));
        foreach (['MOTDEPASSEAPP', 'compte@example.test', 'smtp://', 'stream_socket'] as $leak) {
            $this->assertStringNotContainsString($leak, $r->getContent());
        }

        Log::shouldHaveReceived('error')->withArgs(function ($message, $context = []) {
            $encoded = json_encode($context);

            return $message === 'Erreur test email'
                && ! str_contains($encoded, 'MOTDEPASSEAPP')
                && ! str_contains($encoded, 'compte@example.test');
        })->once();
    }

    public function test_unknown_failure_falls_back_to_a_generic_message(): void
    {
        $this->smtp();
        Mail::shouldReceive('raw')->andThrow(new \RuntimeException('quelque chose d\'inattendu'));

        $this->send()->assertStatus(500)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'journal du serveur'));
    }

    public function test_redact_removes_addresses_and_credentials(): void
    {
        $out = MailDiagnostic::redact("Erreur pour jean.dupont@gmail.com via smtp://user:secret@host:587\n\t  fin");

        $this->assertStringNotContainsString('jean.dupont', $out);
        $this->assertStringNotContainsString('secret', $out);
        $this->assertStringNotContainsString("\n", $out);
        $this->assertLessThanOrEqual(300, mb_strlen(MailDiagnostic::redact(str_repeat('x', 1000))));
    }

    public function test_no_hint_when_the_mailer_is_not_smtp(): void
    {
        config(['mail.default' => 'log']);

        $this->assertNull(MailDiagnostic::configurationHint());
    }
}
