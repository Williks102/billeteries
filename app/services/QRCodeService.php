<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class QRCodeService
{
    /**
     * Méthode principale : génère ou récupère le QR code d'un ticket
     */
    public function getOrGenerateTicketQR(Ticket $ticket, $format = 'base64')
    {
        try {
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
            
        } catch (\Exception $e) {
            Log::error('Erreur service QR code : ' . $e->getMessage(), [
                'ticket_code' => $ticket->ticket_code,
                'format' => $format
            ]);
            return null;
        }
    }
    
    /**
     * Générer un QR code via Google Charts API et retourner en base64
     */
    public function generateTicketQRBase64(Ticket $ticket, $size = 200)
    {
        try {
            // URL de vérification du billet
            $verificationUrl = url("/verify-ticket/{$ticket->ticket_code}");
            
            // URL de l'API Google Charts avec paramètres optimisés
            $qrUrl = "https://chart.googleapis.com/chart?" . http_build_query([
                'chs' => "{$size}x{$size}",
                'cht' => 'qr',
                'chl' => $verificationUrl,
                'choe' => 'UTF-8',
                'chld' => 'M|2' // Correction d'erreur Medium + marge
            ]);
            
            // Récupérer l'image avec timeout
            $response = Http::timeout(15)->get($qrUrl);
            
            if ($response->successful()) {
                $imageData = $response->body();
                
                // Vérifier que c'est bien une image
                if (strlen($imageData) > 100) {
                    return 'data:image/png;base64,' . base64_encode($imageData);
                }
            }
            
            Log::warning('Google Charts API failed for ticket: ' . $ticket->ticket_code);
            return $this->generateFallbackQR($ticket);
            
        } catch (\Exception $e) {
            Log::warning('Erreur génération QR code Google Charts : ' . $e->getMessage());
            return $this->generateFallbackQR($ticket);
        }
    }
    
    /**
     * QR code de secours si Google Charts échoue
     */
    private function generateFallbackQR(Ticket $ticket)
    {
        try {
            // Alternative simple : QR code basique avec API différente
            $verificationUrl = url("/verify-ticket/{$ticket->ticket_code}");
            $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($verificationUrl);
            
            $response = Http::timeout(10)->get($qrUrl);
            
            if ($response->successful() && strlen($response->body()) > 100) {
                return 'data:image/png;base64,' . base64_encode($response->body());
            }
            
        } catch (\Exception $e) {
            Log::error('Fallback QR generation failed: ' . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Générer et sauvegarder un QR code sur le disque
     */
    public function generateAndSaveTicketQR(Ticket $ticket, $size = 200)
    {
        try {
            $verificationUrl = url("/verify-ticket/{$ticket->ticket_code}");
            $qrUrl = "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl=" . urlencode($verificationUrl);
            
            $response = Http::timeout(10)->get($qrUrl);
            
            if ($response->successful()) {
                $imageData = $response->body();
                
                // Créer le répertoire si nécessaire
                $directory = 'public/qrcodes';
                if (!Storage::exists($directory)) {
                    Storage::makeDirectory($directory);
                }
                
                $filename = "qr-{$ticket->ticket_code}.png";
                $filepath = $directory . '/' . $filename;
                
                Storage::put($filepath, $imageData);
                
                // Mettre à jour le ticket
                $ticket->update(['qr_code_path' => $filepath]);
                
                return Storage::url($filepath);
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::warning('Erreur sauvegarde QR code : ' . $e->getMessage());
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
     * Générer QR code pour un billet (méthode de compatibilité)
     */
    public function generateForTicket(Ticket $ticket)
    {
        return $this->getOrGenerateTicketQR($ticket, 'base64');
    }
    
    /**
     * Générer QR codes pour tous les billets d'une commande
     */
    public function generateForOrder($order)
    {
        $tickets = $order->tickets;
        $results = [];
        
        foreach ($tickets as $ticket) {
            $results[$ticket->id] = $this->getOrGenerateTicketQR($ticket, 'base64');
        }
        
        return $results;
    }
    
    /**
     * Tester la génération de QR code
     */
    public function testQRGeneration()
    {
        try {
            $testUrl = url('/test');
            $qrUrl = "https://chart.googleapis.com/chart?chs=100x100&cht=qr&chl=" . urlencode($testUrl);
            
            $response = Http::timeout(5)->get($qrUrl);
            
            return [
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'message' => $response->successful() ? 'QR generation working' : 'QR generation failed',
                'test_qr' => $response->successful() ? 'data:image/png;base64,' . base64_encode($response->body()) : null
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'test_qr' => null
            ];
        }
    }
    
    /**
     * Nettoyer les anciens QR codes
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
}