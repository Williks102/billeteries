<?php

namespace App\Services;

use App\Models\Ticket;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Service QR Code : génération 100 % LOCALE (S-6, S-7).
 *
 * Aucun appel réseau : le code du billet (secret porteur de /verify-ticket/{code}) ne quitte jamais le serveur.
 * Encodage par bacon/bacon-qr-code (dépendance de simple-qrcode), rendu PNG par GD (déjà requis par DomPDF).
 * PNG et non SVG : imagick n'est pas disponible (simple-qrcode ne sait faire du PNG qu'avec imagick), et tous
 * les consommateurs existants (colonne qr_code, téléchargement admin, fichier enregistré, DomPDF) attendent un PNG.
 * Temps de génération : quelques millisecondes ; aucun risque de blocage d'un worker.
 */
class QRCodeService
{
    /** Taille de la marge (zone calme) en modules. */
    private const QUIET_ZONE = 2;

    /**
     * Rend un contenu texte en PNG (données binaires) avec GD, sans réseau.
     */
    public function renderPng(string $content, int $size = 200): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            Log::error('QR : extension GD indisponible.');

            return null;
        }

        try {
            $matrix = Encoder::encode($content, ErrorCorrectionLevel::M(), 'UTF-8')->getMatrix();
            $modules = $matrix->getWidth();
            $total = $modules + 2 * self::QUIET_ZONE;
            $scale = max(1, intdiv(max(21, $size), $total));
            $pixels = $total * $scale;

            $image = imagecreatetruecolor($pixels, $pixels);
            $white = imagecolorallocate($image, 255, 255, 255);
            $dark = imagecolorallocate($image, 26, 26, 26);
            imagefilledrectangle($image, 0, 0, $pixels - 1, $pixels - 1, $white);

            for ($y = 0; $y < $modules; $y++) {
                for ($x = 0; $x < $modules; $x++) {
                    if ($matrix->get($x, $y) === 1) {
                        $px = ($x + self::QUIET_ZONE) * $scale;
                        $py = ($y + self::QUIET_ZONE) * $scale;
                        imagefilledrectangle($image, $px, $py, $px + $scale - 1, $py + $scale - 1, $dark);
                    }
                }
            }

            ob_start();
            imagepng($image);
            $png = ob_get_clean();
            imagedestroy($image);

            return $png !== false && $png !== '' ? $png : null;
        } catch (\Throwable $e) {
            // Pas de message brut (peut contenir le contenu encodé) : classe seulement.
            Log::warning('QR : génération locale échouée (' . get_class($e) . ').');

            return null;
        }
    }

    private function dataUri(?string $png): ?string
    {
        return $png ? 'data:image/png;base64,' . base64_encode($png) : null;
    }

    private function verificationUrl(Ticket $ticket): string
    {
        return url("/verify-ticket/{$ticket->ticket_code}");
    }

    /**
     * QR du billet en data-URI PNG (génération locale).
     */
    public function generateTicketQRBase64(Ticket $ticket, $size = 200)
    {
        $qr = $this->dataUri($this->renderPng($this->verificationUrl($ticket), (int) $size));

        if (! $qr) {
            Log::error("QR : génération impossible pour le billet #{$ticket->id}.");
        }

        return $qr;
    }

    /**
     * Générer et sauvegarder un QR code sur le disque.
     */
    public function generateAndSaveTicketQR(Ticket $ticket, $size = 200)
    {
        try {
            $png = $this->renderPng($this->verificationUrl($ticket), (int) $size);

            if (! $png) {
                Log::warning("QR : impossible de générer le QR du billet #{$ticket->id}.");

                return null;
            }

            $directory = 'public/qrcodes';
            $filepath = "{$directory}/qr-{$ticket->ticket_code}.png";

            Storage::put($filepath, $png);

            try {
                $ticket->update(['qr_code_path' => $filepath]);
            } catch (\Exception $e) {
                Log::warning("QR : qr_code_path non mis à jour pour le billet #{$ticket->id} (" . get_class($e) . ').');
            }

            return Storage::url($filepath);
        } catch (\Exception $e) {
            Log::error("QR : erreur de sauvegarde pour le billet #{$ticket->id} (" . get_class($e) . ').');

            return null;
        }
    }

    /**
     * Vérifier si un QR code existe déjà en cache.
     */
    public function getTicketQRFromCache(Ticket $ticket)
    {
        $filename = "public/qrcodes/qr-{$ticket->ticket_code}.png";

        if (Storage::exists($filename)) {
            return Storage::url($filename);
        }

        return null;
    }

    /**
     * Méthode principale : génère le QR avec cache.
     */
    public function getOrGenerateTicketQR(Ticket $ticket, $format = 'base64')
    {
        if ($format === 'base64') {
            return $this->generateTicketQRBase64($ticket);
        }

        $cachedQR = $this->getTicketQRFromCache($ticket);
        if ($cachedQR) {
            return $cachedQR;
        }

        return $this->generateAndSaveTicketQR($ticket);
    }

    /**
     * QR « stylé » : les options de couleur ne sont plus gérées (QR sombre sur fond blanc, lisible par tous
     * les lecteurs) ; seule la taille est prise en compte.
     */
    public function generateStyledQR(Ticket $ticket, $options = [])
    {
        return $this->generateTicketQRBase64($ticket, (int) ($options['size'] ?? 200));
    }

    /**
     * QR avec informations dans le contenu (local).
     */
    public function generateAdvancedTicketQR(Ticket $ticket, $includeEventInfo = false)
    {
        if (! $includeEventInfo) {
            return $this->generateTicketQRBase64($ticket);
        }

        $event = $ticket->ticketType->event;
        $qrData = json_encode([
            'ticket_code' => $ticket->ticket_code,
            'event' => $event->title,
            'date' => $event->formatted_event_date,
            'venue' => $event->venue,
            'verify_url' => $this->verificationUrl($ticket),
        ]);

        return $this->dataUri($this->renderPng($qrData, 200));
    }

    /**
     * Diagnostic : la génération locale fonctionne-t-elle ? (aucun réseau)
     */
    public function testAllMethods()
    {
        $png = $this->renderPng('https://example.com/test', 150);

        return [
            'local_gd_bacon' => $png ? 'SUCCESS' : 'FAILED',
            'local_size' => $png ? strlen($png) : 0,
            'system_info' => [
                'php_version' => PHP_VERSION,
                'gd_enabled' => extension_loaded('gd'),
            ],
            'summary' => [
                'working_methods_count' => $png ? 1 : 0,
                'working_methods' => $png ? ['Local (GD + bacon/bacon-qr-code)'] : [],
                'recommendation' => $png ? 'QR generation should work' : 'GD manquant ou erreur d\'encodage',
            ],
        ];
    }

    /**
     * Nettoyer les anciens QR codes (commande artisan).
     */
    public function cleanupOldQRCodes($daysOld = 30)
    {
        $deletedCount = 0;
        $files = Storage::files('public/qrcodes');
        $cutoffTime = now()->subDays($daysOld);

        foreach ($files as $file) {
            if (Storage::lastModified($file) < $cutoffTime->timestamp) {
                Storage::delete($file);
                $deletedCount++;
            }
        }

        Log::info("QR cleanup: {$deletedCount} fichiers supprimés");

        return $deletedCount;
    }

    /**
     * Générer un QR code de test (debug), local.
     */
    public function generateTestQR($text = 'Test QR Code')
    {
        return $this->dataUri($this->renderPng((string) $text, 200));
    }

    /**
     * Valider qu'un QR code fonctionne.
     */
    public function validateQRGeneration()
    {
        $testQR = $this->generateTestQR('https://billetterie-ci.com/test');

        return [
            'working' => ! is_null($testQR),
            'api_accessible' => false,
            'message' => $testQR ? 'QR generation working (local)' : 'QR generation failed',
            'test_qr' => $testQR,
        ];
    }
}
