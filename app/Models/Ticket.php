<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_type_id',
        'order_item_id', 
        'ticket_code',
        'status',
        'seat_number',
        'holder_name',
        'holder_email',
        'qr_code_path',
        'validation_data',
        'holder_phone',
        'qr_code',
        'used_at'
    ];

    protected $casts = [
        'used_at' => 'datetime',
    ];

    /**
     * Relations
     */
    public function ticketType()
    {
        return $this->belongsTo(TicketType::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function order()
    {
        return $this->hasOneThrough(Order::class, OrderItem::class, 'id', 'id', 'order_item_id', 'order_id');
    }

    /**
     * Générer un code de billet unique
     */
    public static function generateTicketCode()
    {
        do {
            $code = 'TKT-' . strtoupper(Str::random(8));
        } while (self::where('ticket_code', $code)->exists());
        
        return $code;
    }

    /**
     * Obtenir l'URL du QR code via le service
     */
    public function getQrCodeUrlAttribute()
    {
        try {
            $qrService = app(\App\Services\QRCodeService::class);
            
            // Essayer de récupérer depuis le cache d'abord
            $cachedQR = $qrService->getTicketQRFromCache($this);
            if ($cachedQR) {
                return $cachedQR;
            }
            
            // Sinon générer et sauvegarder
            $qrUrl = $qrService->generateAndSaveTicketQR($this);
            if ($qrUrl) {
                return $qrUrl;
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error("Erreur QR URL pour ticket {$this->ticket_code}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtenir le QR code en base64 pour affichage direct
     */
    public function getQrCodeBase64()
    {
        try {
            $qrService = app(\App\Services\QRCodeService::class);
            $qrBase64 = $qrService->getOrGenerateTicketQR($this, 'base64');
            
            if ($qrBase64) {
                return $qrBase64;
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error("Erreur QR base64 pour ticket {$this->ticket_code}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtenir le QR code en SVG/HTML pour affichage web
     */
    public function getQrCodeSvg()
    {
        try {
            // Essayer de générer un QR PNG et le convertir
            $qrService = app(\App\Services\QRCodeService::class);
            $qrBase64 = $qrService->getOrGenerateTicketQR($this, 'base64');
            
            if ($qrBase64) {
                // Retourner une image HTML
                return '<img src="' . $qrBase64 . '" alt="QR Code" style="width: 200px; height: 200px; border: 1px solid #ddd; border-radius: 5px;">';
            }
            
            // Fallback : placeholder
            return '
            <div style="width: 200px; height: 200px; border: 2px dashed #FF6B35; display: flex; align-items: center; justify-content: center; background: #f8f9fa; border-radius: 8px;">
                <div style="text-align: center; color: #FF6B35; font-weight: bold; font-size: 12px;">
                    <div>⚠️ QR CODE</div>
                    <div style="margin: 10px 0; font-family: monospace; font-size: 10px;">' . $this->ticket_code . '</div>
                    <div style="font-size: 8px;">Code manuel requis</div>
                </div>
            </div>';
            
        } catch (\Exception $e) {
            Log::error("Erreur QR SVG pour ticket {$this->ticket_code}: " . $e->getMessage());
            
            // QR code d'erreur minimal
            return '
            <div style="width: 200px; height: 200px; border: 2px solid #dc3545; display: flex; align-items: center; justify-content: center; background: #fff5f5; border-radius: 8px;">
                <div style="text-align: center; color: #dc3545; font-weight: bold; font-size: 12px;">
                    <div>❌ ERREUR QR</div>
                    <div style="margin: 10px 0; font-family: monospace; font-size: 10px;">' . $this->ticket_code . '</div>
                    <div style="font-size: 8px;">Utilisez le code</div>
                </div>
            </div>';
        }
    }

    /**
     * Générer le QR code (méthode principale)
     */
    public function generateQrCode()
    {
        try {
            $qrService = app(\App\Services\QRCodeService::class);
            $qrUrl = $qrService->generateAndSaveTicketQR($this);
            
            if ($qrUrl) {
                return $qrUrl;
            }
            
            // Fallback : stocker l'URL simple
            $verificationUrl = url("/verify-ticket/{$this->ticket_code}");
            $this->qr_code = $verificationUrl;
            $this->save();
            
            return $this->qr_code;
            
        } catch (\Exception $e) {
            Log::error("Erreur generateQrCode pour ticket {$this->ticket_code}: " . $e->getMessage());
            return null;
        }
    }

    /**
 * Obtenir un QR code fiable pour les PDF
 */
public function getReliableQrCode($size = 150)
{
    try {
        $qrService = app(\App\Services\QRCodeService::class);
        
        // Essayer d'abord la génération normale
        $qrBase64 = $qrService->generateTicketQRBase64($this, $size);
        
        if ($qrBase64 && strlen($qrBase64) > 100) {
            \Log::info("QR fiable généré pour PDF - Ticket: {$this->ticket_code}");
            return $qrBase64;
        }
        
        // Si échec, créer un placeholder informatif
        \Log::warning("QR failed, creating placeholder for ticket: {$this->ticket_code}");
        return $this->createQRPlaceholder($size);
        
    } catch (\Exception $e) {
        \Log::error("Erreur QR fiable pour ticket {$this->ticket_code}: " . $e->getMessage());
        return $this->createQRPlaceholder($size);
    }
}

    /**
 * Créer un placeholder QR si la génération échoue
 */
private function createQRPlaceholder($size = 150)
{
    // Créer une image simple avec GD si disponible
    if (function_exists('imagecreate')) {
        try {
            $image = imagecreatetruecolor($size, $size);
            $white = imagecolorallocate($image, 255, 255, 255);
            $orange = imagecolorallocate($image, 255, 107, 53);
            $black = imagecolorallocate($image, 0, 0, 0);
            
            // Fond blanc
            imagefilledrectangle($image, 0, 0, $size, $size, $white);
            
            // Bordure orange
            imagerectangle($image, 2, 2, $size-3, $size-3, $orange);
            
            // Texte
            $font = 3;
            $text = "BILLET VALIDE";
            $textWidth = imagefontwidth($font) * strlen($text);
            $x = ($size - $textWidth) / 2;
            imagestring($image, $font, $x, $size/2 - 30, $text, $orange);
            
            // Code ticket
            $code = $this->ticket_code;
            $font = 2;
            $codeWidth = imagefontwidth($font) * strlen($code);
            $x = ($size - $codeWidth) / 2;
            imagestring($image, $font, $x, $size/2, $code, $black);
            
            // URL
            $url = "clicbillet.com";
            $font = 1;
            $urlWidth = imagefontwidth($font) * strlen($url);
            $x = ($size - $urlWidth) / 2;
            imagestring($image, $font, $x, $size/2 + 30, $url, $black);
            
            // Capturer l'image
            ob_start();
            imagepng($image);
            $imageData = ob_get_clean();
            imagedestroy($image);
            
            return 'data:image/png;base64,' . base64_encode($imageData);
            
        } catch (\Exception $e) {
            \Log::error("Erreur création placeholder: " . $e->getMessage());
        }
    }
    
    // Fallback ultime : retourner null pour affichage texte
    return null;
}

    /**
     * Marquer le billet comme vendu
     */
    public function markAsSold()
    {
        $this->status = 'sold';
        $this->save();
    }

    /**
 * AJOUT 5 : Marquer le ticket comme utilisé (gestion du scan)
 */
public function markAsUsed($scannedBy = null)
{
    if ($this->status === 'used') {
        \Log::warning("Tentative scan ticket déjà utilisé : {$this->ticket_code}");
        return false;
    }

    if (!$this->isValidForUse()) {
        \Log::warning("Tentative scan ticket invalide : {$this->ticket_code}");
        return false;
    }

    // Utiliser les champs de votre structure
    $updateData = [
        'status' => 'used'
    ];
    
    // Si le champ used_at existe dans votre structure
    if (in_array('used_at', $this->fillable) || $this->hasGetMutator('used_at')) {
        $updateData['used_at'] = now();
    }

    $this->update($updateData);

    \Log::info("Ticket scanné avec succès : {$this->ticket_code}", [
        'scanned_by' => $scannedBy,
        'used_at' => $updateData['used_at'] ?? now()
    ]);

    return true;
}


    /**
     * Vérifier si le billet est valide
     */
    public function isValid()
    {
        return $this->status === 'sold' && 
               $this->ticketType->event->event_date >= now()->toDateString();
    }

    /**
     * Annuler le billet
     */
    public function cancel()
    {
        $this->status = 'cancelled';
        $this->save();
    }

    /**
     * Remettre le billet disponible
     */
    public function makeAvailable()
    {
        $this->status = 'available';
        $this->save();
    }

    /**
     * Vérifications d'état
     */
    public function isAvailable()
    {
        return $this->status === 'available';
    }

    public function isSold()
    {
        return $this->status === 'sold';
    }

    public function isUsed()
    {
        return $this->status === 'used';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    /**
     * Obtenir le propriétaire du billet (acheteur)
     */
    public function owner()
    {
        $order = $this->order()->where('payment_status', 'paid')->first();
        return $order ? $order->user : null;
    }

    /**
     * Informations complètes du billet
     */
    public function getFullTicketInfo()
    {
        $this->load(['ticketType.event.category', 'orderItem.order.user']);
        
        return [
            'ticket_code' => $this->ticket_code,
            'status' => $this->status,
            'holder_name' => $this->holder_name,
            'holder_email' => $this->holder_email,
            'event_title' => $this->ticketType->event->title ?? 'N/A',
            'event_date' => $this->ticketType->event->event_date ? 
                $this->ticketType->event->event_date->format('d/m/Y') : 'N/A',
            'event_time' => $this->ticketType->event->event_time ? 
                $this->ticketType->event->event_time->format('H:i') : 'N/A',
            'seat_number' => $this->seat_number,
            'venue' => $this->ticketType->event->venue ?? 'N/A',
            'price' => $this->ticketType->price ?? 0
        ];
    }

    /**
 * Relation orders() manquante
 */
public function orders()
{
    return $this->belongsToMany(Order::class, 'order_tickets');
}

/**
 * Helper pour vérifier l'appartenance
 */
public function belongsToUser($userId)
{
    if ($this->order_item_id) {
        $orderItem = \App\Models\OrderItem::find($this->order_item_id);
        return $orderItem && $orderItem->order && 
               $orderItem->order->user_id === $userId && 
               $orderItem->order->payment_status === 'paid';
    }
    
    return $this->orders()->where('user_id', $userId)
                          ->where('payment_status', 'paid')
                          ->exists();
    }
/**
 * AJOUT 3 : Obtenir la commande principale (compatible avec votre structure)
 */
public function getMainOrder()
{
    // Priorité : utiliser order_item_id si disponible
    if ($this->order_item_id && $this->orderItem) {
        return $this->orderItem->order;
    }
    
    // Fallback : via la table pivot
    return $this->orders()->where('payment_status', 'paid')->first();
    }

    /**
 * AJOUT 4 : Vérifier si le ticket est valide pour utilisation
 */
public function isValidForUse()
{
    // Vérifications de base
    if ($this->status !== 'sold') {
        return false;
    }
    
    if ($this->status === 'used' || $this->status === 'cancelled') {
        return false;
    }
    
    // Vérifier qu'il y a une commande payée
    $order = $this->getMainOrder();
    if (!$order || $order->payment_status !== 'paid') {
        return false;
    }
    
    // Vérifier que l'événement n'est pas passé
    if ($this->event && $this->event->event_date < now()->toDateString()) {
        return false;
    }
    
    return true;
    }

   /**
 * Informations complètes pour vérification (VERSION CORRIGÉE)
 */
public function getVerificationInfo()
{
    $order = $this->getMainOrder();
    $event = $this->ticketType->event ?? null;
    
    return [
        'is_valid' => $this->isValidForUse(),
        'status' => $this->status,
        'message' => $this->getStatusMessage(),
        'ticket_code' => $this->ticket_code,
        'event' => [
            'title' => $event->title ?? 'Événement non trouvé',
            'date' => $event->event_date ? $event->event_date->format('d/m/Y') : 'Date à déterminer',
            'time' => $event->event_time ? $event->event_time->format('H:i') : 'Heure à déterminer',
            'venue' => $event->venue ?? 'Lieu à déterminer',
            'address' => $event->address ?? null,
            'category' => $event->category->name ?? null
        ],
        'ticket_type' => $this->ticketType->name ?? 'Type de billet inconnu',
        'price' => $this->ticketType->price ?? 0,
        'holder' => [
            'name' => $order ? ($order->user->name ?? 'Nom non disponible') : 'Commande non trouvée',
            'email' => $order ? ($order->user->email ?? 'Email non disponible') : 'Commande non trouvée'
        ],
        'order' => [
            'number' => $order ? ($order->order_number ?? $order->id ?? 'N/A') : 'N/A',
            'date' => $order && $order->created_at ? $order->created_at->format('d/m/Y à H:i') : 'Date inconnue'  // ← LIGNE CORRIGÉE
        ],
        'seat_number' => $this->seat_number,
        'used_at' => $this->used_at,
        'can_be_used' => method_exists($this, 'canBeUsed') ? $this->canBeUsed() : false
    ];
}
/**
 * Obtenir le message de statut du ticket (NOUVELLE MÉTHODE)
 */
public function getStatusMessage()
{
    if ($this->used_at) {
        return 'Billet déjà utilisé le ' . $this->used_at->format('d/m/Y à H:i');
    }

    return match($this->status) {
        'sold' => 'Billet valide et utilisable',
        'cancelled' => 'Billet annulé',
        'expired' => 'Billet expiré',
        'used' => 'Billet déjà utilisé',
        'available' => 'Billet disponible à la vente',
        default => 'Statut du billet: ' . $this->status
    };
}
/**
 * Vérifier si le billet peut être utilisé
 */
public function canBeUsed()
{
    return $this->status === 'sold' && !$this->used_at && $this->ticketType && $this->ticketType->event;
}
}