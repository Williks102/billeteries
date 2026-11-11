<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * P1/P2 : webhook exclu du CSRF, signature obligatoire (fail closed). Sans base de données :
 * toutes les requêtes sont rejetées avant tout accès aux commandes.
 */
class PaiementProWebhookSecurityTest extends TestCase
{
    private const URL = '/payments/paiementpro/notification';

    protected function setUp(): void
    {
        parent::setUp();

        // Pas de .env en CI locale : clé jetable pour chiffrement de session/cookies
        config(['app.key' => 'base64:' . base64_encode(str_repeat('k', 32))]);
        config(['payments.providers.paiementpro.merchant_id' => 'TEST']);
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'merchantId' => 'TEST',
            'referenceNumber' => 'ORD-X',
            'amount' => 1000,
            'responsecode' => '0',
            'transactiondt' => '2026-01-01 00:00:00',
        ], $override);
    }

    public function test_only_the_webhook_is_excluded_from_csrf(): void
    {
        // Le framework ne vérifie pas le CSRF en test : on contrôle la liste d'exclusion réelle.
        $excluded = app(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)->getExcludedPaths();

        $this->assertContains('payments/paiementpro/notification', $excluded);
        $this->assertNotContains('payments/paiementpro/return', $excluded);
        $this->assertNotContains('payments/paiementpro/cancel', $excluded);
    }

    public function test_webhook_without_parameters_is_rejected_with_400(): void
    {
        $this->post(self::URL, [])->assertStatus(400);
    }

    public function test_notification_rejected_when_secret_is_empty(): void
    {
        config(['payments.providers.paiementpro.secret_key' => null]);

        $this->post(self::URL, $this->payload(['hashcode' => hash('sha256', 'TESTORD-X1000')]))
            ->assertStatus(400);
    }

    public function test_notification_rejected_without_hashcode(): void
    {
        config(['payments.providers.paiementpro.secret_key' => 's3cret']);

        $this->post(self::URL, $this->payload())->assertStatus(400);
    }

    public function test_notification_rejected_with_wrong_hashcode(): void
    {
        config(['payments.providers.paiementpro.secret_key' => 's3cret']);

        $this->post(self::URL, $this->payload(['hashcode' => 'forged']))->assertStatus(400);
    }

    public function test_notification_rejected_with_other_merchant_id(): void
    {
        config(['payments.providers.paiementpro.secret_key' => 's3cret']);

        $hash = hash('sha256', 'OTHER' . 'ORD-X' . '1000' . 's3cret');

        $this->post(self::URL, $this->payload(['merchantId' => 'OTHER', 'hashcode' => $hash]))
            ->assertStatus(400);
    }

    public function test_service_can_be_built_without_merchant_id(): void
    {
        config(['payments.providers.paiementpro.merchant_id' => null]);

        $service = app(\App\Services\PaiementProService::class);

        $this->assertFalse($service->isConfigured());
    }

    public function test_test_mode_is_off_by_default(): void
    {
        $this->assertFalse(config('payments.providers.paiementpro.test_mode'));
    }
}
