<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class QRCodeService
{
    /**
     * Générer un QR code via Google Charts API et retourner en base64
     */
    public function generateTicketQRBase64(Ticket $ticket, $size = 200)
    {
        try {
            // URL de vérification du billet
            $verificationUrl = url("/verify-ticket/{$ticket->ticket_code}");
            
            // URL de l'API Google Charts
            $qrUrl = "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl=" . urlencode($verificationUrl);
            
            // Récupérer l'image
            $response = Http::timeout(10)->get($qrUrl);
            
            if ($response->successful()) {
                $imageData = $response->body();
                return 'data:image/png;base64,' . base64_encode($imageData);
            }
            
            return null;
            
        } catch (\Exception $e) {
            \Log::warning('Erreur génération QR code Google Charts : ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Générer et sauvegarder un QR code sur le disque
     */
    public function generateAndSaveTicketQR(Ticket $ticket, $size = 200)
    {
        try {
            $verificationUrl = url("/verify-ticket/{$ticket->ticket_code}");
            $qrUrl = "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl=" . urlencode($verificationUrl);
            
            // Récupérer l'image
            $response = Http::timeout(10)->get($qrUrl);
            
            if ($response->successful()) {
                $imageData = $response->body();
                
                // Chemin de sauvegarde
                $directory = 'public/qrcodes';
                $filename = "qr-{$ticket->ticket_code}.png";
                $filepath = $directory . '/' . $filename;
                
                // Sauvegarder avec Laravel Storage
                Storage::put($filepath, $imageData);
                
                // Retourner l'URL publique
                return Storage::url($filepath);
            }
            
            return null;
            
        } catch (\Exception $e) {
            \Log::warning('Erreur sauvegarde QR code : ' . $e->getMessage());
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
            'chld' => 'M|' . $margin, // Correction d'erreur Medium + marge
            'chco' => $color . '|' . $backgroundColor // Couleur|Background
        ]);
        
        try {
            $response = Http::timeout(10)->get($qrUrl);
            
            if ($response->successful()) {
                return 'data:image/png;base64,' . base64_encode($response->body());
            }
            
        } catch (\Exception $e) {
            \Log::warning('Erreur QR stylé : ' . $e->getMessage());
        }
        
        // Fallback : QR simple
        return $this->generateTicketQRBase64($ticket, $size);
    }
    
    /**
     * Générer QR code avec informations dans l'URL
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
            $response = Http::timeout(10)->get($qrUrl);
            
            if ($response->successful()) {
                return 'data:image/png;base64,' . base64_encode($response->body());
            }
            
        } catch (\Exception $e) {
            \Log::warning('Erreur QR avancé : ' . $e->getMessage());
        }
        
        return $this->generateTicketQRBase64($ticket);
    }
    
    /**
     * Nettoyer les anciens QR codes (commande artisan)
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
        
        \Log::info("QR cleanup: {$deletedCount} fichiers supprimés");
        return $deletedCount;
    }
    
    /**
     * Générer un QR code de test (pour debug)
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
            \Log::error('Test QR failed: ' . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Valider qu'un QR code fonctionne
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