<?php

namespace Tests\Feature;

use Tests\TestCase;

class DemoModeGuardTest extends TestCase
{
    private function load(array $env): array
    {
        foreach (['APP_ENV', 'DEMO_AUTO_CONFIRM_PAYMENTS', 'DEMO_MODE_ACKNOWLEDGED'] as $k) {
            putenv($k . (isset($env[$k]) ? '=' . $env[$k] : ''));
            unset($_ENV[$k], $_SERVER[$k]);
            if (isset($env[$k])) { $_ENV[$k] = $_SERVER[$k] = $env[$k]; }
        }
        return require config_path('payments.php');
    }

    protected function tearDown(): void
    {
        $this->load([]);
        parent::tearDown();
    }

    public function test_guard_matrix(): void
    {
        $c = $this->load(['APP_ENV' => 'production', 'DEMO_AUTO_CONFIRM_PAYMENTS' => 'true']);
        $this->assertFalse($c['demo_auto_confirm']);
        $this->assertTrue($c['demo_blocked_in_production']);

        $c = $this->load(['APP_ENV' => 'production', 'DEMO_AUTO_CONFIRM_PAYMENTS' => 'true', 'DEMO_MODE_ACKNOWLEDGED' => 'true']);
        $this->assertTrue($c['demo_auto_confirm']);
        $this->assertFalse($c['demo_blocked_in_production']);

        $c = $this->load(['APP_ENV' => 'production', 'DEMO_MODE_ACKNOWLEDGED' => 'true']);
        $this->assertFalse($c['demo_auto_confirm']);

        $c = $this->load(['APP_ENV' => 'local', 'DEMO_AUTO_CONFIRM_PAYMENTS' => 'true']);
        $this->assertTrue($c['demo_auto_confirm']);
    }
}
