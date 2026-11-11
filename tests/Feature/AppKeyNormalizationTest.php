<?php

namespace Tests\Feature;

use App\Console\Commands\NormalizeAppKey;
use Tests\TestCase;

/** I2 : une APP_KEY générée hors ligne de commande (par exemple par Render) doit être utilisable. */
class AppKeyNormalizationTest extends TestCase
{
    private function raw(): string
    {
        return random_bytes(32);
    }

    public function test_la_cle_avec_prefixe_est_inchangee(): void
    {
        $key = 'base64:'.base64_encode($this->raw());

        $this->assertSame($key, NormalizeAppKey::normalize($key)['key']);
    }

    public function test_la_cle_base64_sans_prefixe_recoit_le_prefixe(): void
    {
        $bytes = $this->raw();
        $result = NormalizeAppKey::normalize(base64_encode($bytes));

        $this->assertSame('base64:'.base64_encode($bytes), $result['key']);
    }

    public function test_base64_sans_padding_et_url_safe_est_accepte(): void
    {
        $bytes = $this->raw();
        $urlSafe = rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');

        $this->assertSame('base64:'.base64_encode($bytes), NormalizeAppKey::normalize($urlSafe)['key']);
    }

    public function test_32_caracteres_bruts_sont_acceptes_tels_quels(): void
    {
        $raw = str_repeat('a', 32);

        $this->assertSame($raw, NormalizeAppKey::normalize($raw)['key']);
    }

    public function test_les_valeurs_invalides_sont_refusees_sans_etre_affichees(): void
    {
        // Note : base64 de 24 octets = 32 caractères, donc valide comme clé brute de 32 caractères (cas voulu).
        foreach (['', '   ', 'trop-court', str_repeat('x', 31), 'base64:abc', 'base64:'.base64_encode(random_bytes(16)),
            base64_encode(random_bytes(33)), 'base64:***'] as $bad) {
            $result = NormalizeAppKey::normalize($bad);

            $this->assertNull($result['key'], "Devrait être refusée : [$bad]");
            if (strlen(trim($bad)) > 4) {
                $this->assertStringNotContainsString(trim($bad), $result['reason'], 'La valeur ne doit jamais apparaître dans le message.');
            }
        }
    }

    public function test_la_cle_normalisee_est_acceptee_par_le_chiffrement_de_laravel(): void
    {
        $normalized = NormalizeAppKey::normalize(base64_encode($this->raw()))['key'];

        config(['app.key' => $normalized]);
        $this->app->forgetInstance('encrypter');

        $this->assertSame('secret', decrypt(encrypt('secret')));
    }

    public function test_la_commande_affiche_la_cle_normalisee_uniquement_avec_print(): void
    {
        $bytes = $this->raw();
        config(['app.key' => base64_encode($bytes)]);

        $this->artisan('app:normalize-key')->doesntExpectOutputToContain(base64_encode($bytes))->assertExitCode(0);

        config(['app.key' => 'zzz-valeur-secrete']);
        $this->artisan('app:normalize-key', ['--print' => true])->doesntExpectOutputToContain('zzz-valeur-secrete')->assertExitCode(1);
    }
}
