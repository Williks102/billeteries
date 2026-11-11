<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use Tests\Fakes\FakeQRCodeService;

abstract class TestCase extends BaseTestCase
{
    /**
     * I2 : aucun test ne doit toucher le réseau. QRCodeService (API HTTP externes, 8 s de timeout par billet)
     * est remplacé par un faux, et toute requête HTTP réelle lève une exception.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->singleton(\App\Services\QRCodeService::class, fn () => new FakeQRCodeService());
        Http::preventStrayRequests();
    }
}
