<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

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
        'qr_code_path',
        'validation_data',
        'created_ip',
        'sent_at',
        'downloaded_at',
        'download_count',
        'seat_number'
    ];

    protected $casts = [
        'used_at' => 'datetime',
        'sent_at' => 'datetime',
        'downloaded_at' => 'datetime',
        'validation_data' => 'array'
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
     * Scopes
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeSold($query)
    {
        return $query->where('status', 'sold');
    }

    public function scopeUsed($query)
    {
        return $query->where('status', 'used');
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

        try {
            // URL de vérification simple
            $verificationUrl = route('tickets.verify', $this->ticket_code);

            // Générer le QR code
            $qrCode = QrCode::size(300)
                ->color(255, 107, 53) // Orange ClicBillet
                ->backgroundColor(255, 255, 255)
                ->margin(2)
                ->errorCorrection('M')
                ->generate($verificationUrl);

            // Créer le dossier s'il n'existe pas
            $directory = 'tickets/qr_codes';
            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory);
            }

            // Sauvegarder
            $filename = 'qr_' . $this->ticket_code . '.svg';
            $path = $directory . '/' . $filename;
            
            Storage::disk('public')->put($path, $qrCode);
            
            // Mettre à jour le chemin
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
        // Si le QR code existe
        if ($this->qr_code_path && Storage::disk('public')->exists($this->qr_code_path)) {
            return Storage::url($this->qr_code_path);
        }
        
        // Générer si manquant
        $path = $this->generateQrCode();
        return $path ? Storage::url($path) : null;
    }

    /**
     * Obtenir le QR code en SVG pour affichage direct
     */
    public function getQrCodeSvg()
    {
        try {
            // Si le fichier existe, le lire
            if ($this->qr_code_path && Storage::disk('public')->exists($this->qr_code_path)) {
                return Storage::disk('public')->get($this->qr_code_path);
            }

            // Sinon générer directement
            $verificationUrl = route('tickets.verify', $this->ticket_code);

            return QrCode::size(200)
                ->color(255, 107, 53)
                ->backgroundColor(255, 255, 255)
                ->margin(1)
                ->generate($verificationUrl);

        } catch (\Exception $e) {
            \Log::error('Erreur QR SVG: ' . $e->getMessage());
            
            // QR code minimal en cas d'erreur
            return QrCode::size(200)->generate($this->ticket_code);
        }
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
     * Vérifier si le billet est valide
     */
    public function isValid()
    {
        return $this->status === 'sold' && 
               $this->ticketType->event->event_date >= now()->toDateString();
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
                $this->ticketType->event->event_time->format('H:i') : null,
            'event_location' => $this->ticketType->event->venue ?? 'N/A',
            'ticket_type' => $this->ticketType->name ?? 'N/A',
            'price' => $this->ticketType->price ? 
                number_format($this->ticketType->price, 0, ',', ' ') . ' FCFA' : 'N/A',
            'used_at' => $this->used_at ? $this->used_at->format('d/m/Y H:i') : null,
        ];
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
}