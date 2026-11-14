<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Scanner de billets du promoteur (vue lue comme du code source : le comportement réel est vérifié dans un navigateur).
 *
 * - le bip ne vient plus d'un fichier son intégré en `data:` : les deux sons d'origine étaient tronqués (base64 invalide,
 *   plus de la moitié des données manquantes) donc illisibles, en plus d'être bloqués par la CSP ;
 * - la caméra reste allumée après un scan : elle était arrêtée à chaque détection, ce qui laissait un cadre blanc et
 *   obligeait à la relancer pour chaque billet.
 */
class ScannerBeepAndCameraTest extends TestCase
{
    private function source(): string
    {
        return (string) file_get_contents(base_path('resources/views/promoteur/scanner/index.blade.php'));
    }

    public function test_no_embedded_data_uri_sound_remains(): void
    {
        $this->assertStringNotContainsString('data:audio', $this->source());
        $this->assertDoesNotMatchRegularExpression('/new Audio\(/', $this->source());
    }

    public function test_beeps_use_web_audio_and_the_context_is_started_by_a_user_gesture(): void
    {
        $view = $this->source();

        $this->assertStringContainsString('AudioContext', $view);
        $this->assertStringContainsString('createOscillator', $view);
        // Le contexte est créé/relancé dans les fonctions appelées par un clic (démarrage caméra, bouton Vérifier)
        $this->assertMatchesRegularExpression('/async function startCamera\(\) \{\s*ensureAudio\(\)/', $view);
        $this->assertMatchesRegularExpression('/async function processTicket\(\) \{\s*ensureAudio\(\)/', $view);
        // Deux sons distincts : valide et refusé
        $this->assertStringContainsString('function playSuccessSound()', $view);
        $this->assertStringContainsString('function playErrorSound()', $view);
    }

    public function test_sound_is_played_from_the_server_answer_not_on_detection(): void
    {
        $view = $this->source();
        $detection = substr($view, strpos($view, 'function startQRDetection'), strpos($view, '// ========== GESTION BOUTONS CAMÉRA') - strpos($view, 'function startQRDetection'));

        $this->assertStringNotContainsString('playSuccessSound', $detection, 'un billet refusé ne doit pas sonner comme un billet valide');
        $this->assertStringContainsString('playSuccessSound();', substr($view, strpos($view, 'function showSuccess')));
        $this->assertStringContainsString('playErrorSound();', substr($view, strpos($view, 'function showError')));
    }

    public function test_camera_stays_on_after_a_detection_and_detection_resumes_by_itself(): void
    {
        $view = $this->source();
        $detection = substr($view, strpos($view, 'function startQRDetection'), strpos($view, '// ========== GESTION BOUTONS CAMÉRA') - strpos($view, 'function startQRDetection'));

        $this->assertStringNotContainsString('stopCamera()', $detection, 'la caméra ne doit pas être coupée à chaque scan');
        $this->assertStringContainsString('scanBusy = true', $detection);
        $this->assertStringContainsString('SCAN_RESUME_DELAY', $detection);
        $this->assertStringContainsString('if (scanBusy)', $detection, 'la détection est suspendue pendant la vérification');
    }
}
