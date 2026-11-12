<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
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
        //'holder_name',
        //'holder_email',
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
     * Commande du billet, billets récents (order_item_id) ET anciens (pivot order_tickets seul).
     * N'ajoute aucune requête si `orderItem.order` / `orders` sont chargés (eager loading des listes).
     */
    public function linkedOrder(): ?Order
    {
        $order = ($this->order_item_id && $this->orderItem) ? $this->orderItem->order : null;

        return $order ?? ($this->relationLoaded('orders') ? $this->orders->first() : $this->orders()->first());
    }

    /**
     * Transitions de statut autorisées pour l'admin (règles historiques du contrôleur, centralisées) :
     * vendu -> utilisé (commande payée requise), non utilisé -> annulé, annulé -> vendu. Un billet utilisé est figé.
     *
     * @return list<string>
     */
    public function adminTransitions(): array
    {
        return match ($this->status) {
            'sold' => $this->isIssued() ? ['used', 'cancelled'] : ['cancelled'],
            'cancelled' => $this->linkedOrder()?->payment_status === 'paid' ? ['sold'] : [],
            'used' => [],
            default => ['cancelled'], // available, reserved
        };
    }

    public function canTransitionTo(string $to): bool
    {
        return in_array($to, $this->adminTransitions(), true);
    }

    /**
     * Statistiques : billets rattachés aux commandes données (pivot order_tickets OU order_item_id,
     * selon l'ancienneté du billet). $orders : requête Eloquent sur orders.
     */
    public function scopeOfOrders($query, $orders)
    {
        $ids = $orders->clone()->select('orders.id');

        return $query->where(function ($q) use ($ids) {
            $q->whereIn('tickets.id', \Illuminate\Support\Facades\DB::table('order_tickets')->select('ticket_id')->whereIn('order_id', $ids))
                ->orWhereIn('tickets.order_item_id', \Illuminate\Support\Facades\DB::table('order_items')->select('id')->whereIn('order_id', $ids));
        });
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

    /** Préfixe d'une data-URI PNG valide (seul format accepté dans tickets.qr_code). */
    private const QR_DATA_URI_PREFIX = 'data:image/png;base64,';

    /**
     * Le billet est-il ÉMIS ? Statut vendu/utilisé ET commande payée. Un billet réservé (commande en
     * attente), annulé ou dont la commande n'est pas payée n'a pas de QR visible.
     */
    public function isIssued(): bool
    {
        if (!in_array($this->status, ['sold', 'used'], true)) {
            return false;
        }

        try {
            $order = $this->linkedOrder();

            return $order !== null && $order->payment_status === 'paid';
        } catch (\Throwable $e) {
            Log::warning("Billet #{$this->id} : état d'émission indéterminé (" . get_class($e) . ').');

            return false;
        }
    }

    /**
     * QR du billet en data-URI PNG, ou null si le billet n'est pas émis (décision : pas de QR avant paiement).
     * Idempotent : renvoie qr_code s'il contient déjà une data-URI, sinon le génère (local, sans réseau,
     * sans fichier), le persiste dans tickets.qr_code (text) et le retourne. Ne lève jamais d'exception.
     */
    public function qrDataUri(int $size = 300): ?string
    {
        try {
            if (!$this->isIssued()) {
                return null;
            }

            if (is_string($this->qr_code) && str_starts_with($this->qr_code, self::QR_DATA_URI_PREFIX)) {
                return $this->qr_code;
            }

            $uri = app(\App\Services\QRCodeService::class)->generateTicketQRBase64($this, $size);
            if (!is_string($uri) || !str_starts_with($uri, self::QR_DATA_URI_PREFIX)) {
                return null;
            }

            // Persistance : un échec d'écriture n'empêche pas l'affichage du QR généré.
            try {
                $this->qr_code = $uri;
                $this->saveQuietly();
            } catch (\Throwable $e) {
                Log::warning("QR : persistance impossible pour le billet #{$this->id} (" . get_class($e) . ').');
            }

            return $uri;
        } catch (\Throwable $e) {
            Log::error("Erreur QR pour le billet #{$this->id} (" . get_class($e) . ').');

            return null;
        }
    }

    /** Compatibilité des vues existantes : data-URI (jamais d'URL de fichier), null avant paiement. */
    public function getQrCodeUrlAttribute()
    {
        return $this->qrDataUri();
    }

    /** QR en base64 pour affichage direct/PDF : null tant que le billet n'est pas émis. */
    public function getQrCodeBase64()
    {
        return $this->qrDataUri();
    }

    /** QR en HTML pour affichage web : vide tant que le billet n'est pas émis. */
    public function getQrCodeSvg()
    {
        $qr = $this->qrDataUri();

        if ($qr === null) {
            return $this->isIssued()
                ? '<div style="width: 200px; height: 200px; border: 2px dashed #FF6B35; display: flex; align-items: center; justify-content: center; font-family: monospace; font-size: 10px;">' . e($this->ticket_code) . '</div>'
                : '';
        }

        return '<img src="' . $qr . '" alt="QR Code" style="width: 200px; height: 200px; border: 1px solid #ddd; border-radius: 5px;">';
    }

    /** Génère/persiste le QR (billet émis uniquement) ; compatibilité des commandes Artisan existantes. */
    public function generateQrCode()
    {
        return $this->qrDataUri();
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
            return $qrBase64;
        }
        
        // Si échec, créer un placeholder informatif
        \Log::warning("QR failed, creating placeholder for ticket #{$this->id}");
        return $this->createQRPlaceholder($size);
        
    } catch (\Exception $e) {
        \Log::error("Erreur QR fiable pour le billet #{$this->id} (" . get_class($e) . ").");
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
            \Log::error("Erreur création placeholder (" . get_class($e) . ").");
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
        \Log::warning("Tentative scan ticket déjà utilisé : #{$this->id}");
        return false;
    }

    if (!$this->isValidForUse()) {
        \Log::warning("Tentative scan ticket invalide : #{$this->id}");
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

    \Log::info("Ticket scanné avec succès : #{$this->id}", [
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
            //'holder_name' => $this->holder_name,
            //'holder_email' => $this->holder_email,
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