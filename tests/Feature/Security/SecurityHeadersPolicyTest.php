<?php

namespace Tests\Feature\Security;

use Tests\Concerns\BuildsOrderSchema;
use Tests\TestCase;

/** En-têtes : Permissions-Policy (caméra du scanner autorisée) et HSTS sans preload. */
class SecurityHeadersPolicyTest extends TestCase
{
    use BuildsOrderSchema;

    private function assertPolicyHeaders($response): void
    {
        $policy = (string) $response->headers->get('Permissions-Policy');
        $this->assertStringContainsString('camera=(self)', $policy);
        foreach (['microphone=()', 'geolocation=()', 'payment=()', 'usb=()'] as $off) {
            $this->assertStringContainsString($off, $policy);
        }

        $hsts = (string) $response->headers->get('Strict-Transport-Security');
        $this->assertStringContainsString('max-age=31536000', $hsts);
        $this->assertStringContainsString('includeSubDomains', $hsts);
        $this->assertStringNotContainsString('preload', $hsts);

        // la CSP n'est pas touchée par ce chantier
        $this->assertStringContainsString("script-src 'self' 'unsafe-inline'", (string) $response->headers->get('Content-Security-Policy'));
    }

    public function test_public_page_has_the_headers(): void
    {
        config(['app.key' => 'base64:'.base64_encode(str_repeat('k', 32))]);
        $this->assertPolicyHeaders($this->get('/login')->assertOk());
    }

    public function test_admin_page_has_the_headers(): void
    {
        $this->buildOrderSchema();
        (include base_path('database/migrations/2026_11_13_100000_create_activity_logs_table.php'))->up();
        $admin = $this->makeUser(10, 'admin');

        $this->assertPolicyHeaders($this->actingAs($admin)->get(route('admin.activity.index'))->assertOk());
    }
}
