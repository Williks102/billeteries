<?php

// ========== MISE À JOUR DU MODÈLE TICKET ==========

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;

class Ticket extends Model
{
    protected $fillable = [
        'order_item_id',
        'ticket_type_id',
        'ticket_code',
        'status',
        'holder_name',
        'holder_email',
        'holder_phone',
        'used_at',
        'qr_code_path'
    ];

    protected $casts = [
        'used_at' => 'datetime',
    ];

    /**
     * Relations
     */
    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function ticketType()
    {
        return $this->belongsTo(TicketType::class);
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
     * Générer le QR code du billet
     */
    public function generateQrCode()
    {
        if (!$this->ticket_code) {
            return null;
        }

        // URL de vérification du billet
        $verificationUrl = route('tickets.verify', $this->ticket_code);
        
        // Données à encoder dans le QR code
        $qrData = json_encode([
            'ticket_code' => $this->ticket_code,
            'event_id' => $this->ticketType->event_id,
            'holder_name' => $this->holder_name,
            'verification_url' => $verificationUrl,
            'generated_at' => now()->toISOString(),
        ]);

        try {
            // Générer le QR code en SVG (plus léger et vectoriel)
            $qrCode = QrCode::size(300)
                ->style('round')
                ->eye('circle')
                ->gradient(50, 150, 200, 100, 100, 255, 'diagonal')
                ->margin(2)
                ->errorCorrection('M')
                ->generate($qrData);

            // Sauvegarder en tant que SVG
            $filename = 'qr_' . $this->ticket_code . '.svg';
            $path = 'tickets/qr_codes/' . $filename;
            
            \Storage::disk('public')->put($path, $qrCode);
            
            // Mettre à jour le chemin dans la base de données
            $this->update(['qr_code_path' => $path]);
            
            return $path;
            
        } catch (\Exception $e) {
            \Log::error('Erreur génération QR code: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtenir l'URL du QR code
     */
    public function getQrCodeUrlAttribute()
    {
        if ($this->qr_code_path && \Storage::disk('public')->exists($this->qr_code_path)) {
            return \Storage::url($this->qr_code_path);
        }
        
        // Générer le QR code s'il n'existe pas
        $path = $this->generateQrCode();
        return $path ? \Storage::url($path) : null;
    }

    /**
     * Obtenir le QR code en tant que string SVG
     */
    public function getQrCodeSvg()
    {
        $verificationUrl = route('tickets.verify', $this->ticket_code);
        
        $qrData = json_encode([
            'ticket_code' => $this->ticket_code,
            'event_id' => $this->ticketType->event_id,
            'holder_name' => $this->holder_name,
            'verification_url' => $verificationUrl,
        ]);

        return QrCode::size(200)
            ->style('round')
            ->eye('circle')
            ->color(255, 107, 53) // Orange ClicBillet
            ->backgroundColor(255, 255, 255)
            ->margin(1)
            ->errorCorrection('M')
            ->generate($qrData);
    }

    /**
     * Marquer le billet comme utilisé
     */
    public function markAsUsed()
    {
        $this->update([
            'status' => 'used',
            'used_at' => now()
        ]);
    }

    /**
     * Vérifier si le billet est valide pour utilisation
     */
    public function isValid()
    {
        return $this->status === 'sold' && 
               $this->ticketType->event->event_date >= now()->toDateString();
    }

    /**
     * Obtenir toutes les informations du billet pour affichage
     */
    public function getFullTicketInfo()
    {
        $this->load(['ticketType.event.category', 'orderItem.order.user']);
        
        return [
            'ticket_code' => $this->ticket_code,
            'status' => $this->status,
            'holder_name' => $this->holder_name,
            'holder_email' => $this->holder_email,
            'event_title' => $this->ticketType->event->title,
            'event_date' => $this->ticketType->event->event_date->format('d/m/Y'),
            'event_time' => $this->ticketType->event->event_time ? $this->ticketType->event->event_time->format('H:i') : null,
            'venue' => $this->ticketType->event->venue,
            'address' => $this->ticketType->event->address,
            'ticket_type' => $this->ticketType->name,
            'price' => $this->orderItem->unit_price,
            'category' => $this->ticketType->event->category->name ?? 'Événement',
            'used_at' => $this->used_at ? $this->used_at->format('d/m/Y H:i') : null,
            'qr_code_url' => $this->qr_code_url,
        ];
    }

    /**
     * Scopes
     */
    public function scopeSold($query)
    {
        return $query->where('status', 'sold');
    }

    public function scopeUsed($query)
    {
        return $query->where('status', 'used');
    }

    public function scopeValid($query)
    {
        return $query->where('status', 'sold')
                    ->whereHas('ticketType.event', function($q) {
                        $q->where('event_date', '>=', now()->toDateString());
                    });
    }
}