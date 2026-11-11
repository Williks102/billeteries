<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    /**
     * Aucun test ne doit toucher le réseau : toute requête HTTP réelle lève une exception. Le vrai QRCodeService est
     * utilisé (S-6 : génération 100 % locale, quelques ms, aucun réseau) ; tests/Fakes/FakeQRCodeService reste
     * disponible pour les tests qui observent les appels (niveau de transaction).
     */
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        Storage::fake(); // le vrai QRCodeService écrit des PNG : jamais dans storage/app du développeur
    }
}
