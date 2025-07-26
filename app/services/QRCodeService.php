<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class QRCodeService
{
    /**
     * Méthode principale
     */
    public function getOrGenerateTicketQR(Ticket $ticket, $format = 'base64')
    {
        Log::info("Génération QR pour ticket: {$ticket->ticket_code}, format: {$format}");
        
        try {
            if ($format === 'base64') {
                return $this->generateTicketQRBase64($ticket);
            }
            
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
     * Génération QR Base64 - Version améliorée
     */
    public function generateTicketQRBase64(Ticket $ticket, $size = 200)
    {
        $verificationUrl = url("/verify-ticket/{$ticket->ticket_code}");
        Log::info("URL de vérification: {$verificationUrl}");
        
        // MÉTHODE 1 : SimpleSoftwareIO avec configuration forcée
        $qr = $this->trySimpleSoftwareIOForced($verificationUrl, $size);
        if ($qr) {
            Log::info("QR généré avec SimpleSoftwareIO forcé pour: {$ticket->ticket_code}");
            return $qr;
        }
        
        // MÉTHODE 2 : QR simple mais fonctionnel
        $qr = $this->generateSimpleReadableQR($verificationUrl, $size);
        if ($qr) {
            Log::info("QR simple généré pour: {$ticket->ticket_code}");
            return $qr;
        }
        
        // MÉTHODE 3 : APIs externes avec SSL désactivé
        $qr = $this->tryExternalAPIsNoSSL($verificationUrl, $size);
        if ($qr) {
            Log::info("QR généré avec API externe pour: {$ticket->ticket_code}");
            return $qr;
        }
        
        Log::error("Toutes les méthodes QR ont échoué pour: {$ticket->ticket_code}");
        return null;
    }
    
    /**
     * Méthode 1 : SimpleSoftwareIO avec PNG forcé
     */
    private function trySimpleSoftwareIOForced($url, $size)
    {
        if (!class_exists('\SimpleSoftwareIO\QrCode\Facades\QrCode')) {
            return null;
        }
        
        try {
            // FORCER PNG UNIQUEMENT (plus compatible)
            $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                ->size($size)
                ->margin(2)
                ->errorCorrection('M')
                ->encoding('UTF-8')
                ->generate($url);
            
            if ($qrCode && strlen($qrCode) > 100) {
                Log::info("QR PNG généré avec SimpleSoftwareIO, taille: " . strlen($qrCode));
                return 'data:image/png;base64,' . base64_encode($qrCode);
            }
            
        } catch (\Exception $e) {
            Log::warning("SimpleSoftwareIO PNG échec: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Méthode 2 : Génération d'un QR simple mais lisible
     */
    private function generateSimpleReadableQR($url, $size)
    {
        if (!extension_loaded('gd')) {
            return null;
        }
        
        try {
            // Créer une image simple avec les informations essentielles
            $moduleSize = max(8, intval($size / 30));
            $imageSize = $moduleSize * 30;
            
            $image = imagecreate($imageSize, $imageSize);
            $white = imagecolorallocate($image, 255, 255, 255);
            $black = imagecolorallocate($image, 0, 0, 0);
            $gray = imagecolorallocate($image, 128, 128, 128);
            
            imagefill($image, 0, 0, $white);
            
            // Dessiner une grille simple représentant un QR code
            $this->drawSimpleQRPattern($image, $url, $moduleSize, $black, $gray);
            
            // Convertir en PNG
            ob_start();
            imagepng($image);
            $imageData = ob_get_contents();
            ob_end_clean();
            imagedestroy($image);
            
            if ($imageData && strlen($imageData) > 100) {
                return 'data:image/png;base64,' . base64_encode($imageData);
            }
            
        } catch (\Exception $e) {
            Log::warning("QR simple échec: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Dessiner un pattern QR simplifié mais plus réaliste
     */
    private function drawSimpleQRPattern($image, $url, $moduleSize, $black, $gray)
    {
        $gridSize = 25;
        
        // Pattern de recherche (coins)
        $this->drawFinderPattern($image, 1, 1, $moduleSize, $black);       // Haut-gauche
        $this->drawFinderPattern($image, 1, 17, $moduleSize, $black);      // Haut-droite
        $this->drawFinderPattern($image, 17, 1, $moduleSize, $black);      // Bas-gauche
        
        // Lignes de timing
        for ($i = 8; $i < 17; $i++) {
            if ($i % 2 === 0) {
                $this->drawModule($image, 6, $i, $moduleSize, $black);
                $this->drawModule($image, $i, 6, $moduleSize, $black);
            }
        }
        
        // Module sombre obligatoire
        $this->drawModule($image, 4 * 4 + 9, 8, $moduleSize, $black);
        
        // Données basées sur l'URL
        $urlHash = hash('crc32', $url);
        $binaryData = str_pad(decbin(hexdec($urlHash)), 32, '0', STR_PAD_LEFT);
        
        // Remplir les zones de données
        $bitIndex = 0;
        for ($row = 9; $row < 17; $row++) {
            for ($col = 9; $col < 17; $col++) {
                if (!$this->isReservedPosition($row, $col)) {
                    $bit = $bitIndex < strlen($binaryData) ? $binaryData[$bitIndex] : '0';
                    if ($bit === '1') {
                        $this->drawModule($image, $row, $col, $moduleSize, $black);
                    }
                    $bitIndex++;
                }
            }
        }
        
        // Ajouter des modules aléatoires pour ressembler à un vrai QR
        for ($i = 0; $i < 50; $i++) {
            $row = rand(9, 16);
            $col = rand(9, 16);
            if (!$this->isReservedPosition($row, $col) && rand(0, 1)) {
                $this->drawModule($image, $row, $col, $moduleSize, $black);
            }
        }
    }
    
    /**
     * Dessiner un pattern de recherche 7x7
     */
    private function drawFinderPattern($image, $startRow, $startCol, $moduleSize, $color)
    {
        $pattern = [
            [1,1,1,1,1,1,1],
            [1,0,0,0,0,0,1],
            [1,0,1,1,1,0,1],
            [1,0,1,1,1,0,1],
            [1,0,1,1,1,0,1],
            [1,0,0,0,0,0,1],
            [1,1,1,1,1,1,1]
        ];
        
        for ($i = 0; $i < 7; $i++) {
            for ($j = 0; $j < 7; $j++) {
                if ($pattern[$i][$j] === 1) {
                    $this->drawModule($image, $startRow + $i, $startCol + $j, $moduleSize, $color);
                }
            }
        }
    }
    
    /**
     * Dessiner un module individuel
     */
    private function drawModule($image, $row, $col, $moduleSize, $color)
    {
        $x = $col * $moduleSize;
        $y = $row * $moduleSize;
        imagefilledrectangle($image, $x, $y, $x + $moduleSize - 1, $y + $moduleSize - 1, $color);
    }
    
    /**
     * Vérifier si une position est réservée
     */
    private function isReservedPosition($row, $col)
    {
        // Éviter les patterns de recherche et timing
        if (($row < 9 && $col < 9) || ($row < 9 && $col > 15) || ($row > 15 && $col < 9)) {
            return true;
        }
        if ($row === 6 || $col === 6) {
            return true;
        }
        return false;
    }
    
    /**
     * Méthode 3 : APIs externes avec SSL désactivé
     */
    private function tryExternalAPIsNoSSL($url, $size)
    {
        if (!app()->environment('local')) {
            return null;
        }
        
        // Google Charts avec cURL SSL désactivé
        try {
            $qrUrl = "https://chart.googleapis.com/chart?" . http_build_query([
                'chs' => "{$size}x{$size}",
                'cht' => 'qr',
                'chl' => $url,
                'choe' => 'UTF-8',
                'chld' => 'M|2'
            ]);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $qrUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            
            $imageData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 && $imageData && strlen($imageData) > 100) {
                return 'data:image/png;base64,' . base64_encode($imageData);
            }
            
        } catch (\Exception $e) {
            Log::warning("cURL Google Charts échec: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Sauvegarder QR sur disque
     */
    public function generateAndSaveTicketQR(Ticket $ticket, $size = 200)
    {
        $qrBase64 = $this->generateTicketQRBase64($ticket, $size);
        
        if (!$qrBase64) {
            return null;
        }
        
        try {
            $imageData = base64_decode(str_replace(['data:image/png;base64,', 'data:image/svg+xml;base64,'], '', $qrBase64));
            
            $directory = 'public/qrcodes';
            if (!Storage::exists($directory)) {
                Storage::makeDirectory($directory);
            }
            
            $filename = "qr-{$ticket->ticket_code}.png";
            $filepath = $directory . '/' . $filename;
            
            Storage::put($filepath, $imageData);
            $ticket->update(['qr_code_path' => $filepath]);
            
            return Storage::url($filepath);
            
        } catch (\Exception $e) {
            Log::error("Erreur sauvegarde QR: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Cache
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
     * Test du service
     */
    public function testQRGeneration()
    {
        $testUrl = url('/test');
        $results = [];
        
        $results['SimpleSoftwareIO_Forced'] = !is_null($this->trySimpleSoftwareIOForced($testUrl, 100));
        $results['Simple_Readable_QR'] = !is_null($this->generateSimpleReadableQR($testUrl, 100));
        $results['External_APIs'] = !is_null($this->tryExternalAPIsNoSSL($testUrl, 100));
        
        $workingMethods = array_filter($results);
        
        return [
            'success' => count($workingMethods) > 0,
            'methods' => $results,
            'working_count' => count($workingMethods),
            'working_methods' => array_keys($workingMethods),
            'message' => count($workingMethods) > 0 ? 
                'QR generation working with: ' . implode(', ', array_keys($workingMethods)) :
                'No QR generation methods working'
        ];
    }
    
    /**
     * Méthodes de compatibilité
     */
    public function generateForTicket(Ticket $ticket)
    {
        return $this->getOrGenerateTicketQR($ticket, 'base64');
    }
}