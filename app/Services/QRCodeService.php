<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * Service QR Code - Version corrigée et améliorée
 * Compatible avec votre système existant
 */
class QRCodeService
{
    /**
     * Générer un QR code via multiples méthodes avec fallbacks
     * Version améliorée de votre méthode actuelle
     */
    public function generateTicketQRBase64(Ticket $ticket, $size = 200)
    {
        Log::info("Génération QR pour ticket: {$ticket->ticket_code}");
        
        // Méthode 1: Google Charts AMÉLIORÉ (votre méthode actuelle mais robuste)
        $qr = $this->generateWithGoogleChartsEnhanced($ticket, $size);
        if ($qr) {
            Log::info("QR généré avec Google Charts amélioré");
            return $qr;
        }
        
        // Méthode 2: SimpleSoftwareIO si disponible
        if (class_exists('\SimpleSoftwareIO\QrCode\Facades\QrCode')) {
            $qr = $this->generateWithSimpleSoftwareIO($ticket, $size);
            if ($qr) {
                Log::info("QR généré avec SimpleSoftwareIO");
                return $qr;
            }
        }
        
        // Méthode 3: API QR Server (alternative)
        $qr = $this->generateWithQRServer($ticket, $size);
        if ($qr) {
            Log::info("QR généré avec QR Server");
            return $qr;
        }
        
        // Méthode 4: Fallback file_get_contents simple
        $qr = $this->generateWithFileGetContents($ticket, $size);
        if ($qr) {
            Log::info("QR généré avec file_get_contents");
            return $qr;
        }
        
        Log::error("Toutes les méthodes QR ont échoué pour: {$ticket->ticket_code}");
        return null;
    }
    
    /**
     * Méthode 1: Google Charts AMÉLIORÉE avec plusieurs tentatives
     */
    private function generateWithGoogleChartsEnhanced(Ticket $ticket, $size)
    {
        try {
            $verificationUrl = url("/verify-ticket/{$ticket->ticket_code}");
            
            // Essayer plusieurs configurations
            $configs = [
                // Config standard
                [
                    'chs' => "{$size}x{$size}",
                    'cht' => 'qr',
                    'chl' => $verificationUrl,
                    'choe' => 'UTF-8'
                ],
                // Config avec correction d'erreur
                [
                    'chs' => "{$size}x{$size}",
                    'cht' => 'qr',
                    'chl' => $verificationUrl,
                    'choe' => 'UTF-8',
                    'chld' => 'H|1'  // Haute correction, marge 1
                ],
                // Config simplifiée
                [
                    'chs' => "{$size}x{$size}",
                    'cht' => 'qr',
                    'chl' => $verificationUrl
                ]
            ];
            
            foreach ($configs as $configIndex => $config) {
                $qrUrl = "https://chart.googleapis.com/chart?" . http_build_query($config);
                
                try {
                    // Méthode 1: Http::get (votre méthode actuelle)
                    $response = Http::timeout(8)->get($qrUrl);
                    
                    if ($response->successful() && strlen($response->body()) > 100) {
                        Log::info("Google Charts config #{$configIndex} réussie");
                        return 'data:image/png;base64,' . base64_encode($response->body());
                    }
                    
                } catch (\Exception $e) {
                    Log::warning("HTTP client config #{$configIndex} failed, trying file_get_contents: " . $e->getMessage());
                    
                    // Méthode 2: file_get_contents en fallback
                    $context = stream_context_create([
                        'http' => [
                            'timeout' => 5,
                            'method' => 'GET',
                            'header' => 'User-Agent: Laravel/QRService'
                        ]
                    ]);
                    
                    $imageData = @file_get_contents($qrUrl, false, $context);
                    
                    if ($imageData && strlen($imageData) > 100) {
                        Log::info("Google Charts file_get_contents config #{$configIndex} réussie");
                        return 'data:image/png;base64,' . base64_encode($imageData);
                    }
                }
            }
            
        } catch (\Exception $e) {
            Log::warning('Google Charts amélioré échoué : ' . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Méthode 2: SimpleSoftwareIO (si installé)
     */
    private function generateWithSimpleSoftwareIO(Ticket $ticket, $size)
    {
        try {
            $verificationUrl = url("/verify-ticket/{$ticket->ticket_code}");
            
            $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                ->size($size)
                ->margin(1)
                ->errorCorrection('H')
                ->encoding('UTF-8')
                ->generate($verificationUrl);
            
            if ($qrCode && strlen($qrCode) > 100) {
                return 'data:image/png;base64,' . base64_encode($qrCode);
            }
            
        } catch (\Exception $e) {
            Log::warning('SimpleSoftwareIO échoué : ' . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Méthode 3: QR Server API (alternative)
     */
    private function generateWithQRServer(Ticket $ticket, $size)
    {
        try {
            $verificationUrl = url("/verify-ticket/{$ticket->ticket_code}");
            
            $apiUrl = "https://api.qrserver.com/v1/create-qr-code/?" . http_build_query([
                'size' => "{$size}x{$size}",
                'data' => $verificationUrl,
                'format' => 'png',
                'ecc' => 'H'
            ]);
            
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'method' => 'GET',
                    'header' => 'User-Agent: Laravel/QRService'
                ]
            ]);
            
            $imageData = @file_get_contents($apiUrl, false, $context);
            
            if ($imageData && strlen($imageData) > 100) {
                return 'data:image/png;base64,' . base64_encode($imageData);
            }
            
        } catch (\Exception $e) {
            Log::warning('QR Server échoué : ' . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Méthode 4: Fallback simple avec file_get_contents
     */
    private function generateWithFileGetContents(Ticket $ticket, $size)
    {
        try {
            $verificationUrl = url("/verify-ticket/{$ticket->ticket_code}");
            $qrUrl = "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl=" . urlencode($verificationUrl);
            
            // Context très simple
            $context = stream_context_create([
                'http' => [
                    'timeout' => 3,
                    'ignore_errors' => true
                ]
            ]);
            
            $imageData = @file_get_contents($qrUrl, false, $context);
            
            if ($imageData && strlen($imageData) > 50) {
                return 'data:image/png;base64,' . base64_encode($imageData);
            }
            
        } catch (\Exception $e) {
            Log::warning('file_get_contents fallback échoué : ' . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Générer et sauvegarder un QR code sur le disque
     * Version améliorée de votre méthode actuelle
     */
    public function generateAndSaveTicketQR(Ticket $ticket, $size = 200)
    {
        try {
            // Générer le QR en base64 avec les nouvelles méthodes
            $qrBase64 = $this->generateTicketQRBase64($ticket, $size);
            
            if (!$qrBase64) {
                Log::warning("Impossible de générer QR pour ticket {$ticket->ticket_code}");
                return null;
            }
            
            // Extraire les données de l'image
            $imageData = base64_decode(str_replace('data:image/png;base64,', '', $qrBase64));
            
            // Sauvegarder
            $directory = 'public/qrcodes';
            $filename = "qr-{$ticket->ticket_code}.png";
            $filepath = "{$directory}/{$filename}";
            
            Storage::put($filepath, $imageData);
            
            // Optionnel: mettre à jour le ticket avec le chemin
            try {
                $ticket->update(['qr_code_path' => $filepath]);
            } catch (\Exception $e) {
                Log::warning("Impossible de mettre à jour qr_code_path pour ticket {$ticket->ticket_code}: " . $e->getMessage());
            }
            
            $url = Storage::url($filepath);
            Log::info("QR sauvegardé avec succès pour {$ticket->ticket_code}: {$url}");
            
            return $url;
            
        } catch (\Exception $e) {
            Log::error("Erreur sauvegarde QR pour {$ticket->ticket_code}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Vérifier si un QR code existe déjà en cache
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
     * Méthode principale : génère le QR avec cache
     * Compatible avec votre code existant
     */
    public function getOrGenerateTicketQR(Ticket $ticket, $format = 'base64')
    {
        // Pour PDF (base64), générer à la volée
        if ($format === 'base64') {
            return $this->generateTicketQRBase64($ticket);
        }
        
        // Pour URL, vérifier le cache d'abord
        $cachedQR = $this->getTicketQRFromCache($ticket);
        if ($cachedQR) {
            return $cachedQR;
        }
        
        return $this->generateAndSaveTicketQR($ticket);
    }
    
    /**
     * Générer QR code personnalisé avec style
     * Votre méthode existante améliorée
     */
    public function generateStyledQR(Ticket $ticket, $options = [])
    {
        $verificationUrl = url("/verify-ticket/{$ticket->ticket_code}");
        
        // Options par défaut
        $size = $options['size'] ?? 200;
        $margin = $options['margin'] ?? 1;
        $color = $options['color'] ?? '1a1a1a'; // Noir par défaut
        $backgroundColor = $options['bg_color'] ?? 'ffffff'; // Blanc par défaut
        
        // URL Google Charts avec options
        $qrUrl = "https://chart.googleapis.com/chart?" . http_build_query([
            'chs' => "{$size}x{$size}",
            'cht' => 'qr',
            'chl' => $verificationUrl,
            'choe' => 'UTF-8',
            'chld' => 'H|' . $margin, // Haute correction + marge
            'chco' => $color . '|' . $backgroundColor // Couleur|Background
        ]);
        
        try {
            $response = Http::timeout(8)->get($qrUrl);
            
            if ($response->successful()) {
                return 'data:image/png;base64,' . base64_encode($response->body());
            }
            
        } catch (\Exception $e) {
            Log::warning('Erreur QR stylé : ' . $e->getMessage());
        }
        
        // Fallback : QR simple
        return $this->generateTicketQRBase64($ticket, $size);
    }
    
    /**
     * Générer QR code avec informations dans l'URL
     * Votre méthode existante maintenue
     */
    public function generateAdvancedTicketQR(Ticket $ticket, $includeEventInfo = false)
    {
        if ($includeEventInfo) {
            // Inclure plus d'infos dans le QR code
            $qrData = json_encode([
                'ticket_code' => $ticket->ticket_code,
                'event' => $ticket->ticketType->event->title,
                'date' => $ticket->ticketType->event->formatted_event_date,
                'venue' => $ticket->ticketType->event->venue,
                'verify_url' => url("/verify-ticket/{$ticket->ticket_code}")
            ]);
        } else {
            // URL simple
            $qrData = url("/verify-ticket/{$ticket->ticket_code}");
        }
        
        $qrUrl = "https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=" . urlencode($qrData);
        
        try {
            $response = Http::timeout(8)->get($qrUrl);
            
            if ($response->successful()) {
                return 'data:image/png;base64,' . base64_encode($response->body());
            }
            
        } catch (\Exception $e) {
            Log::warning('Erreur QR avancé : ' . $e->getMessage());
        }
        
        return $this->generateTicketQRBase64($ticket);
    }
    
    /**
     * Méthode de diagnostic - Tester toutes les méthodes
     * NOUVELLE - pour identifier les problèmes
     */
    public function testAllMethods()
    {
        $testUrl = "https://example.com/test";
        $results = [];
        
        Log::info("=== DIAGNOSTIC QR CODES ===");
        
        // Test Google Charts avec Http
        try {
            $qrUrl = "https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl=" . urlencode($testUrl);
            $response = Http::timeout(5)->get($qrUrl);
            $results['google_charts_http'] = $response->successful() && strlen($response->body()) > 100 ? 'SUCCESS' : 'FAILED';
            if ($response->successful()) {
                $results['google_charts_http_size'] = strlen($response->body());
            }
        } catch (\Exception $e) {
            $results['google_charts_http'] = 'ERROR: ' . $e->getMessage();
        }
        
        // Test file_get_contents
        try {
            $qrUrl = "https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl=" . urlencode($testUrl);
            $context = stream_context_create(['http' => ['timeout' => 5]]);
            $data = @file_get_contents($qrUrl, false, $context);
            $results['file_get_contents'] = $data && strlen($data) > 100 ? 'SUCCESS' : 'FAILED';
            if ($data) {
                $results['file_get_contents_size'] = strlen($data);
            }
        } catch (\Exception $e) {
            $results['file_get_contents'] = 'ERROR: ' . $e->getMessage();
        }
        
        // Test SimpleSoftwareIO
        if (class_exists('\SimpleSoftwareIO\QrCode\Facades\QrCode')) {
            try {
                $qr = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(150)->generate($testUrl);
                $results['simplesoftwareio'] = $qr && strlen($qr) > 100 ? 'SUCCESS' : 'FAILED';
                if ($qr) {
                    $results['simplesoftwareio_size'] = strlen($qr);
                }
            } catch (\Exception $e) {
                $results['simplesoftwareio'] = 'ERROR: ' . $e->getMessage();
            }
        } else {
            $results['simplesoftwareio'] = 'NOT_INSTALLED';
        }
        
        // Test QR Server
        try {
            $apiUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($testUrl);
            $context = stream_context_create(['http' => ['timeout' => 5]]);
            $data = @file_get_contents($apiUrl, false, $context);
            $results['qr_server'] = $data && strlen($data) > 100 ? 'SUCCESS' : 'FAILED';
            if ($data) {
                $results['qr_server_size'] = strlen($data);
            }
        } catch (\Exception $e) {
            $results['qr_server'] = 'ERROR: ' . $e->getMessage();
        }
        
        // Infos système
        $results['system_info'] = [
            'php_version' => PHP_VERSION,
            'curl_enabled' => function_exists('curl_init'),
            'gd_enabled' => extension_loaded('gd'),
            'openssl_enabled' => extension_loaded('openssl'),
            'allow_url_fopen' => ini_get('allow_url_fopen') ? 'YES' : 'NO',
            'user_agent_blocked' => ini_get('user_agent') ?: 'default'
        ];
        
        // Résumé
        $workingMethods = array_filter([
            $results['google_charts_http'] === 'SUCCESS' ? 'Google Charts (Http)' : null,
            $results['file_get_contents'] === 'SUCCESS' ? 'file_get_contents' : null,
            ($results['simplesoftwareio'] ?? null) === 'SUCCESS' ? 'SimpleSoftwareIO' : null,
            $results['qr_server'] === 'SUCCESS' ? 'QR Server' : null,
        ]);
        
        $results['summary'] = [
            'working_methods_count' => count($workingMethods),
            'working_methods' => $workingMethods,
            'recommendation' => count($workingMethods) > 0 ? 'QR generation should work' : 'All methods failed - check network/firewall'
        ];
        
        Log::info('Diagnostic QR codes terminé', $results);
        return $results;
    }
    
    /**
     * Nettoyer les anciens QR codes (commande artisan)
     * Votre méthode existante maintenue
     */
    public function cleanupOldQRCodes($daysOld = 30)
    {
        $deletedCount = 0;
        $files = Storage::files('public/qrcodes');
        $cutoffTime = now()->subDays($daysOld);
        
        foreach ($files as $file) {
            $lastModified = Storage::lastModified($file);
            
            if ($lastModified < $cutoffTime->timestamp) {
                Storage::delete($file);
                $deletedCount++;
            }
        }
        
        Log::info("QR cleanup: {$deletedCount} fichiers supprimés");
        return $deletedCount;
    }
    
    /**
     * Générer un QR code de test (pour debug)
     * Votre méthode existante améliorée
     */
    public function generateTestQR($text = 'Test QR Code')
    {
        $qrUrl = "https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=" . urlencode($text);
        
        try {
            $response = Http::timeout(5)->get($qrUrl);
            
            if ($response->successful()) {
                return 'data:image/png;base64,' . base64_encode($response->body());
            }
            
        } catch (\Exception $e) {
            Log::error('Test QR failed: ' . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Valider qu'un QR code fonctionne
     * Votre méthode existante maintenue
     */
    public function validateQRGeneration()
    {
        $testQR = $this->generateTestQR('https://billetterie-ci.com/test');
        
        return [
            'working' => !is_null($testQR),
            'api_accessible' => !is_null($testQR),
            'message' => $testQR ? 'QR generation working' : 'QR generation failed',
            'test_qr' => $testQR
        ];
    }
}