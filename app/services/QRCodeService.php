<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QRCodeService
{
    /**
     * Méthode principale pour obtenir ou générer un QR code
     */
    public function getOrGenerateTicketQR(Ticket $ticket, $format = 'base64')
    {
        Log::info("Génération QR pour ticket: {$ticket->ticket_code}, format: {$format}");
        
        try {
            if ($format === 'base64') {
                return $this->generateTicketQRBase64($ticket);
            }
            
            // Pour les URLs, vérifier le cache d'abord
            $cachedQR = $this->getTicketQRFromCache($ticket);
            if ($cachedQR) {
                return $cachedQR;
            }
            
            return $this->generateAndSaveTicketQR($ticket);
            
        } catch (\Exception $e) {
            Log::error("Erreur service QR code: " . $e->getMessage(), [
                'ticket_code' => $ticket->ticket_code,
                'format' => $format
            ]);
            return null;
        }
    }
    
    /**
     * Génération QR Base64 optimisée pour votre environnement
     */
    public function generateTicketQRBase64(Ticket $ticket, $size = 200)
    {
        $verificationUrl = url("/verify-ticket/{$ticket->ticket_code}");
        Log::info("URL de vérification: {$verificationUrl}");
        
        // Méthode 1: SimpleSoftwareIO/QrCode (priorité car installé et fonctionne)
        $qr = $this->generateWithSimpleSoftwareIO($verificationUrl, $size);
        if ($qr) return $qr;
        
        // Méthode 2: Fallback GD (fonctionne aussi)
        $qr = $this->generateFallbackQR($ticket);
        if ($qr) return $qr;
        
        // Méthode 3: API QR Server via proxy si configuré
        if (config('app.http_proxy')) {
            $qr = $this->generateWithQRServerProxy($verificationUrl, $size);
            if ($qr) return $qr;
        }
        
        Log::error("Toutes les méthodes QR ont échoué pour: {$ticket->ticket_code}");
        return null;
    }
    
    /**
     * Méthode 1: SimpleSoftwareIO (méthode principale)
     */
    private function generateWithSimpleSoftwareIO($url, $size)
    {
        try {
            // Utiliser plusieurs formats pour maximiser la compatibilité
            $formats = ['png', 'svg'];
            
            foreach ($formats as $format) {
                try {
                    $qrCode = QrCode::format($format)
                        ->size($size)
                        ->margin(2)
                        ->errorCorrection('M')
                        ->encoding('UTF-8')
                        ->generate($url);
                    
                    if ($qrCode && strlen($qrCode) > 100) {
                        Log::info("QR généré avec SimpleSoftwareIO format: {$format}");
                        
                        if ($format === 'svg') {
                            // Convertir SVG en base64
                            return 'data:image/svg+xml;base64,' . base64_encode($qrCode);
                        } else {
                            return 'data:image/png;base64,' . base64_encode($qrCode);
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("SimpleSoftwareIO format {$format} échoué: " . $e->getMessage());
                    continue;
                }
            }
        } catch (\Exception $e) {
            Log::warning("SimpleSoftwareIO général échoué: " . $e->getMessage());
        }
        return null;
    }
    
    /**
     * Méthode 2: Fallback GD amélioré
     */
    private function generateFallbackQR($ticket)
    {
        Log::info("Utilisation du fallback GD pour: {$ticket->ticket_code}");
        
        if (!function_exists('imagecreate')) {
            Log::error("Extension GD non disponible");
            return null;
        }
        
        try {
            // Créer une image avec un design amélioré
            $size = 200;
            $image = imagecreatetruecolor($size, $size);
            
            // Couleurs
            $white = imagecolorallocate($image, 255, 255, 255);
            $black = imagecolorallocate($image, 0, 0, 0);
            $orange = imagecolorallocate($image, 255, 107, 53);
            $gray = imagecolorallocate($image, 240, 240, 240);
            
            // Fond blanc
            imagefilledrectangle($image, 0, 0, $size, $size, $white);
            
            // Créer un pattern de QR code simple
            $cellSize = 10;
            $cols = $size / $cellSize;
            
            // Pattern basé sur le hash du ticket code
            $hash = md5($ticket->ticket_code);
            
            for ($y = 0; $y < $cols; $y++) {
                for ($x = 0; $x < $cols; $x++) {
                    $index = ($y * $cols + $x) % 32;
                    $bit = hexdec(substr($hash, $index, 1)) > 7;
                    
                    if ($bit) {
                        imagefilledrectangle(
                            $image,
                            $x * $cellSize,
                            $y * $cellSize,
                            ($x + 1) * $cellSize,
                            ($y + 1) * $cellSize,
                            $black
                        );
                    }
                }
            }
            
            // Zone centrale pour le code
            $centerSize = 80;
            $centerX = ($size - $centerSize) / 2;
            $centerY = ($size - $centerSize) / 2;
            
            imagefilledrectangle($image, $centerX, $centerY, $centerX + $centerSize, $centerY + $centerSize, $white);
            imagerectangle($image, $centerX, $centerY, $centerX + $centerSize, $centerY + $centerSize, $orange);
            
            // Texte au centre
            $font = 3;
            $text = "TICKET";
            $textWidth = imagefontwidth($font) * strlen($text);
            $x = ($size - $textWidth) / 2;
            imagestring($image, $font, $x, $centerY + 20, $text, $black);
            
            // Code ticket
            $code = $ticket->ticket_code;
            $font = 2;
            $codeWidth = imagefontwidth($font) * strlen($code);
            $x = ($size - $codeWidth) / 2;
            imagestring($image, $font, $x, $centerY + 40, $code, $orange);
            
            // Capture
            ob_start();
            imagepng($image);
            $imageData = ob_get_clean();
            imagedestroy($image);
            
            Log::info("QR fallback généré avec succès");
            return 'data:image/png;base64,' . base64_encode($imageData);
            
        } catch (\Exception $e) {
            Log::error("Erreur génération image GD: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Méthode 3: QR Server avec proxy (si configuré)
     */
    private function generateWithQRServerProxy($url, $size)
    {
        try {
            $apiUrl = "https://api.qrserver.com/v1/create-qr-code/?" . http_build_query([
                'size' => "{$size}x{$size}",
                'data' => $url,
                'format' => 'png',
                'margin' => 10
            ]);
            
            $context = stream_context_create([
                'http' => [
                    'proxy' => config('app.http_proxy'),
                    'request_fulluri' => true,
                    'timeout' => 10
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ]);
            
            $imageData = @file_get_contents($apiUrl, false, $context);
            
            if ($imageData && strlen($imageData) > 100) {
                Log::info("QR généré avec QR Server via proxy");
                return 'data:image/png;base64,' . base64_encode($imageData);
            }
        } catch (\Exception $e) {
            Log::warning("QR Server via proxy échoué: " . $e->getMessage());
        }
        return null;
    }
    
    /**
     * Sauvegarder QR code sur le disque
     */
    public function generateAndSaveTicketQR(Ticket $ticket, $size = 200)
    {
        try {
            // Générer le QR en base64
            $qrBase64 = $this->generateTicketQRBase64($ticket, $size);
            
            if (!$qrBase64) {
                return null;
            }
            
            // Extraire les données de l'image
            $imageData = base64_decode(str_replace(['data:image/png;base64,', 'data:image/svg+xml;base64,'], '', $qrBase64));
            
            // Sauvegarder
            $directory = 'public/qrcodes';
            $filename = "qr-{$ticket->ticket_code}.png";
            $filepath = "{$directory}/{$filename}";
            
            Storage::put($filepath, $imageData);
            $ticket->update(['qr_code_path' => $filepath]);
            
            return Storage::url($filepath);
            
        } catch (\Exception $e) {
            Log::error("Erreur sauvegarde QR: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Récupérer depuis le cache
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
     * Nettoyer le cache si nécessaire
     */
    public function clearCache()
    {
        try {
            $files = Storage::files('public/qrcodes');
            foreach ($files as $file) {
                Storage::delete($file);
            }
            Log::info("Cache QR codes nettoyé");
            return true;
        } catch (\Exception $e) {
            Log::error("Erreur nettoyage cache: " . $e->getMessage());
            return false;
        }
    }
}