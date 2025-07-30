<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * Service pour la génération de QR codes
 * Version complète et optimisée
 */
class QRCodeService
{
    private const DEFAULT_SIZE = 200;
    private const DEFAULT_TIMEOUT = 10;
    private const QR_CACHE_DIRECTORY = 'public/qrcodes';
    
    /**
     * Méthode principale : génère ou récupère le QR avec cache
     */
    public function getOrGenerateTicketQR(Ticket $ticket, string $format = 'url'): ?string
    {
        return match($format) {
            'base64' => $this->generateTicketQRBase64($ticket),
            'url' => $this->getTicketQRFromCacheOrGenerate($ticket),
            default => throw new \InvalidArgumentException("Format non supporté: {$format}")
        };
    }
    
    /**
     * Générer un QR code via Google Charts API et retourner en base64
     */
    public function generateTicketQRBase64(Ticket $ticket, int $size = self::DEFAULT_SIZE): ?string
    {
        try {
            $verificationUrl = $this->getVerificationUrl($ticket);
            $qrUrl = $this->buildGoogleChartsUrl($verificationUrl, $size);
            
            $response = Http::timeout(self::DEFAULT_TIMEOUT)->get($qrUrl);
            
            if ($response->successful()) {
                $imageData = $response->body();
                return 'data:image/png;base64,' . base64_encode($imageData);
            }
            
            Log::warning('Échec génération QR Google Charts', [
                'ticket_code' => $ticket->ticket_code,
                'status' => $response->status(),
                'url' => $qrUrl
            ]);
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('Erreur génération QR code base64', [
                'ticket_code' => $ticket->ticket_code,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Vérifier le cache ou générer et sauvegarder un QR code
     */
    private function getTicketQRFromCacheOrGenerate(Ticket $ticket): ?string
    {
        // D'abord vérifier le cache
        $cachedQR = $this->getTicketQRFromCache($ticket);
        if ($cachedQR) {
            return $cachedQR;
        }
        
        // Sinon générer et sauvegarder
        return $this->generateAndSaveTicketQR($ticket);
    }
    
    /**
     * Vérifier si un QR code existe déjà en cache
     */
    public function getTicketQRFromCache(Ticket $ticket): ?string
    {
        $filename = $this->getCacheFilename($ticket);
        
        if (Storage::exists($filename)) {
            return Storage::url($filename);
        }
        
        return null;
    }
    
    /**
     * Générer et sauvegarder un QR code sur le disque
     */
    public function generateAndSaveTicketQR(Ticket $ticket, int $size = self::DEFAULT_SIZE): ?string
    {
        try {
            $verificationUrl = $this->getVerificationUrl($ticket);
            $qrUrl = $this->buildGoogleChartsUrl($verificationUrl, $size);
            
            $response = Http::timeout(self::DEFAULT_TIMEOUT)->get($qrUrl);
            
            if ($response->successful()) {
                $imageData = $response->body();
                $filepath = $this->getCacheFilename($ticket);
                
                // Créer le répertoire si nécessaire
                Storage::makeDirectory(dirname($filepath));
                
                // Sauvegarder avec Laravel Storage
                Storage::put($filepath, $imageData);
                
                Log::info('QR code généré et sauvegardé', [
                    'ticket_code' => $ticket->ticket_code,
                    'filepath' => $filepath
                ]);
                
                return Storage::url($filepath);
            }
            
            Log::warning('Échec génération QR pour sauvegarde', [
                'ticket_code' => $ticket->ticket_code,
                'status' => $response->status()
            ]);
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('Erreur sauvegarde QR code', [
                'ticket_code' => $ticket->ticket_code,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Générer QR code avec style personnalisé
     */
    public function generateStyledQR(Ticket $ticket, array $options = []): ?string
    {
        $verificationUrl = $this->getVerificationUrl($ticket);
        
        $size = $options['size'] ?? self::DEFAULT_SIZE;
        $margin = $options['margin'] ?? 1;
        $color = $options['color'] ?? '1a1a1a';
        $backgroundColor = $options['bg_color'] ?? 'ffffff';
        
        $qrUrl = "https://chart.googleapis.com/chart?" . http_build_query([
            'chs' => "{$size}x{$size}",
            'cht' => 'qr',
            'chl' => $verificationUrl,
            'choe' => 'UTF-8',
            'chld' => 'M|' . $margin,
            'chco' => $color . '|' . $backgroundColor
        ]);
        
        try {
            $response = Http::timeout(self::DEFAULT_TIMEOUT)->get($qrUrl);
            
            if ($response->successful()) {
                return 'data:image/png;base64,' . base64_encode($response->body());
            }
            
        } catch (\Exception $e) {
            Log::warning('Erreur QR stylé', [
                'ticket_code' => $ticket->ticket_code,
                'error' => $e->getMessage()
            ]);
        }
        
        // Fallback : QR simple
        return $this->generateTicketQRBase64($ticket, $size);
    }
    
    /**
     * Générer QR code avec informations enrichies
     */
    public function generateAdvancedTicketQR(Ticket $ticket, bool $includeEventInfo = false): ?string
    {
        if ($includeEventInfo) {
            // Inclure plus d'infos dans le QR code
            $qrData = json_encode([
                'ticket_code' => $ticket->ticket_code,
                'event' => $ticket->ticketType->event->title,
                'date' => $ticket->ticketType->event->event_date->format('Y-m-d H:i'),
                'venue' => $ticket->ticketType->event->venue,
                'verify_url' => $this->getVerificationUrl($ticket)
            ]);
        } else {
            // URL simple
            $qrData = $this->getVerificationUrl($ticket);
        }
        
        $qrUrl = $this->buildGoogleChartsUrl($qrData, self::DEFAULT_SIZE);
        
        try {
            $response = Http::timeout(self::DEFAULT_TIMEOUT)->get($qrUrl);
            
            if ($response->successful()) {
                return 'data:image/png;base64,' . base64_encode($response->body());
            }
            
        } catch (\Exception $e) {
            Log::warning('Erreur QR avancé', [
                'ticket_code' => $ticket->ticket_code,
                'error' => $e->getMessage()
            ]);
        }
        
        return $this->generateTicketQRBase64($ticket);
    }
    
    /**
     * Nettoyer les anciens QR codes (commande artisan)
     */
    public function cleanupOldQRCodes(int $daysOld = 30): int
    {
        $deletedCount = 0;
        $files = Storage::files(self::QR_CACHE_DIRECTORY);
        $cutoffTime = now()->subDays($daysOld);
        
        foreach ($files as $file) {
            try {
                $lastModified = Storage::lastModified($file);
                
                if ($lastModified < $cutoffTime->timestamp) {
                    Storage::delete($file);
                    $deletedCount++;
                }
            } catch (\Exception $e) {
                Log::warning('Erreur suppression QR cache', [
                    'file' => $file,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        Log::info("QR cleanup: {$deletedCount} fichiers supprimés");
        return $deletedCount;
    }
    
    /**
     * Générer un QR code de test (pour debug)
     */
    public function generateTestQR(string $text = 'Test QR Code'): ?string
    {
        $qrUrl = $this->buildGoogleChartsUrl($text, self::DEFAULT_SIZE);
        
        try {
            $response = Http::timeout(self::DEFAULT_TIMEOUT)->get($qrUrl);
            
            if ($response->successful()) {
                return 'data:image/png;base64,' . base64_encode($response->body());
            }
            
        } catch (\Exception $e) {
            Log::error('Erreur génération QR test', [
                'text' => $text,
                'error' => $e->getMessage()
            ]);
        }
        
        return null;
    }
    
    /**
     * Test de toutes les méthodes (pour diagnostic)
     */
    public function testAllMethods(): array
    {
        $results = [
            'success' => true,
            'tests' => []
        ];
        
        // Test QR simple
        try {
            $testQR = $this->generateTestQR('Test Simple');
            $results['tests']['simple_qr'] = [
                'success' => $testQR !== null,
                'length' => $testQR ? strlen($testQR) : 0
            ];
        } catch (\Exception $e) {
            $results['tests']['simple_qr'] = [
                'success' => false,
                'error' => $e->getMessage()
            ];
            $results['success'] = false;
        }
        
        // Test avec ticket si disponible
        try {
            $ticket = Ticket::first();
            if ($ticket) {
                $ticketQR = $this->generateTicketQRBase64($ticket);
                $results['tests']['ticket_qr'] = [
                    'success' => $ticketQR !== null,
                    'ticket_code' => $ticket->ticket_code,
                    'length' => $ticketQR ? strlen($ticketQR) : 0
                ];
            } else {
                $results['tests']['ticket_qr'] = [
                    'success' => false,
                    'error' => 'Aucun ticket disponible pour le test'
                ];
            }
        } catch (\Exception $e) {
            $results['tests']['ticket_qr'] = [
                'success' => false,
                'error' => $e->getMessage()
            ];
            $results['success'] = false;
        }
        
        // Test stockage
        try {
            $testPath = self::QR_CACHE_DIRECTORY . '/test.txt';
            Storage::put($testPath, 'test');
            $exists = Storage::exists($testPath);
            Storage::delete($testPath);
            
            $results['tests']['storage'] = [
                'success' => $exists,
                'directory' => self::QR_CACHE_DIRECTORY
            ];
        } catch (\Exception $e) {
            $results['tests']['storage'] = [
                'success' => false,
                'error' => $e->getMessage()
            ];
            $results['success'] = false;
        }
        
        // Test de connectivité Google Charts
        try {
            $testUrl = "https://chart.googleapis.com/chart?chs=100x100&cht=qr&chl=test";
            $response = Http::timeout(5)->get($testUrl);
            
            $results['tests']['google_charts_connectivity'] = [
                'success' => $response->successful(),
                'status' => $response->status(),
                'response_size' => $response->successful() ? strlen($response->body()) : 0
            ];
            
            if (!$response->successful()) {
                $results['success'] = false;
            }
        } catch (\Exception $e) {
            $results['tests']['google_charts_connectivity'] = [
                'success' => false,
                'error' => $e->getMessage()
            ];
            $results['success'] = false;
        }
        
        // Test de génération avec style
        try {
            $ticket = Ticket::first();
            if ($ticket) {
                $styledQR = $this->generateStyledQR($ticket, [
                    'size' => 150,
                    'color' => 'ff0000',
                    'bg_color' => 'ffffff'
                ]);
                
                $results['tests']['styled_qr'] = [
                    'success' => $styledQR !== null,
                    'length' => $styledQR ? strlen($styledQR) : 0
                ];
            } else {
                $results['tests']['styled_qr'] = [
                    'success' => false,
                    'error' => 'Aucun ticket pour tester le QR stylé'
                ];
            }
        } catch (\Exception $e) {
            $results['tests']['styled_qr'] = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
        
        return $results;
    }
    
    /**
     * Régénérer tous les QR codes manquants pour les tickets vendus
     */
    public function regenerateMissingQRCodes(): array
    {
        $results = [
            'total_tickets' => 0,
            'generated' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        $ticketsWithoutQR = Ticket::where('status', 'sold')
            ->whereNull('qr_code')
            ->get();
            
        $results['total_tickets'] = $ticketsWithoutQR->count();
        
        foreach ($ticketsWithoutQR as $ticket) {
            try {
                $qr = $this->generateTicketQRBase64($ticket);
                if ($qr) {
                    $ticket->update(['qr_code' => $qr]);
                    $results['generated']++;
                    
                    Log::info('QR code régénéré', [
                        'ticket_code' => $ticket->ticket_code
                    ]);
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Échec génération pour {$ticket->ticket_code}";
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Erreur pour {$ticket->ticket_code}: " . $e->getMessage();
                
                Log::error('Erreur régénération QR', [
                    'ticket_code' => $ticket->ticket_code,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $results;
    }
    
    /**
     * Obtenir les statistiques des QR codes
     */
    public function getQRStats(): array
    {
        $stats = [
            'total_tickets' => Ticket::count(),
            'sold_tickets' => Ticket::where('status', 'sold')->count(),
            'tickets_with_qr' => Ticket::whereNotNull('qr_code')->count(),
            'tickets_without_qr' => Ticket::where('status', 'sold')->whereNull('qr_code')->count(),
            'cached_files' => 0,
            'cache_size_mb' => 0
        ];
        
        try {
            $files = Storage::files(self::QR_CACHE_DIRECTORY);
            $stats['cached_files'] = count($files);
            
            $totalSize = 0;
            foreach ($files as $file) {
                try {
                    $totalSize += Storage::size($file);
                } catch (\Exception $e) {
                    // Ignorer les erreurs de fichiers individuels
                }
            }
            $stats['cache_size_mb'] = round($totalSize / 1024 / 1024, 2);
        } catch (\Exception $e) {
            Log::warning('Erreur calcul stats QR cache', [
                'error' => $e->getMessage()
            ]);
        }
        
        return $stats;
    }
    
    // ==================== MÉTHODES PRIVÉES ====================
    
    /**
     * Construire l'URL de vérification pour un ticket
     */
    private function getVerificationUrl(Ticket $ticket): string
    {
        return url("/verify-ticket/{$ticket->ticket_code}");
    }
    
    /**
     * Construire l'URL Google Charts pour le QR code
     */
    private function buildGoogleChartsUrl(string $data, int $size): string
    {
        return "https://chart.googleapis.com/chart?" . http_build_query([
            'chs' => "{$size}x{$size}",
            'cht' => 'qr',
            'chl' => $data,
            'choe' => 'UTF-8'
        ]);
    }
    
    /**
     * Obtenir le nom de fichier cache pour un ticket
     */
    private function getCacheFilename(Ticket $ticket): string
    {
        return self::QR_CACHE_DIRECTORY . "/qr-{$ticket->ticket_code}.png";
    }
    
    /**
     * Valider un ticket pour la génération de QR
     */
    private function validateTicketForQR(Ticket $ticket): bool
    {
        return !empty($ticket->ticket_code) && 
               $ticket->ticketType && 
               $ticket->ticketType->event;
    }
    
    /**
     * Nettoyer le répertoire de cache (créer s'il n'existe pas)
     */
    private function ensureCacheDirectoryExists(): void
    {
        if (!Storage::exists(self::QR_CACHE_DIRECTORY)) {
            Storage::makeDirectory(self::QR_CACHE_DIRECTORY);
        }
    }
}