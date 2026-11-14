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
        $this->assertStringContainsString('scheduleScanResume', $detection, 'la reprise se fait après l\'affichage du résultat');
        $this->assertStringContainsString('if (scanBusy)', $detection, 'la détection est suspendue pendant la vérification');
    }

    public function test_the_result_is_shown_over_the_camera_and_detection_waits_for_it(): void
    {
        $view = $this->source();

        // Bandeau superposé à la caméra, bouton « Scanner le suivant », durée d'affichage
        $this->assertStringContainsString('id="scanOverlay"', $view);
        $this->assertStringContainsString('data-on-click="scanner-next"', $view);
        $this->assertStringContainsString('SCAN_RESULT_HOLD', $view);
        $this->assertStringContainsString("'scanner-next': function () { dismissScanResult(); }", $view);
        // Le bandeau est affiché par les deux chemins de résultat
        $this->assertStringContainsString("showScanOverlay('ok'", substr($view, strpos($view, 'function showSuccess')));
        $this->assertStringContainsString('showScanOverlay(kind', substr($view, strpos($view, 'function showError')));
        // Les résultats précédents restent consultables
        $this->assertStringContainsString('id="scanHistory"', $view);
        $this->assertStringContainsString('function addScanHistory', $view);
        // La feuille de style dédiée existe et définit les trois états
        $css = (string) file_get_contents(public_path('css/scanner.css'));
        foreach (['.scan-overlay--ok', '.scan-overlay--error', '.scan-overlay--used'] as $selector) {
            $this->assertStringContainsString($selector, $css);
        }
    }

    public function test_server_values_are_escaped_before_being_put_in_html(): void
    {
        $view = $this->source();

        $this->assertStringContainsString('function escapeHtml', $view);
        // Aucune valeur issue du serveur n'est injectée brute dans innerHTML
        foreach (['${message}', '${ticket.client ||', '${ticket.event ||', '${ticket.code}', '${ticket.type ||', '${ticket.status}', '${ticket.used_at}'] as $raw) {
            $this->assertStringNotContainsString($raw, $view, 'valeur non échappée : '.$raw);
        }
    }
}
