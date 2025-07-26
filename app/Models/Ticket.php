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
     * Marquer le billet comme vendu
     */
    public function markAsSold()
    {
        $this->status = 'sold';
        $this->save();
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
}