<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_type_id',
        'ticket_code',
        'qr_code',
        'status',
        'seat_number'
    ];

    /**
     * Relations
     */
    public function ticketType()
    {
        return $this->belongsTo(TicketType::class);
    }

    public function event()
    {
        return $this->hasOneThrough(Event::class, TicketType::class, 'id', 'id', 'ticket_type_id', 'event_id');
    }

    public function orderTickets()
    {
        return $this->hasMany(OrderTicket::class);
    }

    public function order()
    {
        return $this->belongsToMany(Order::class, 'order_tickets');
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
 * Générer le QR code pour ce billet
 */
public function generateQRCode()
{
    // URL de vérification du billet
    $verificationUrl = url("/verify-ticket/{$this->ticket_code}");
    
    // Pour l'instant, on stocke juste l'URL (on ajoutera le vrai QR code plus tard)
    $this->qr_code = $verificationUrl;
    $this->save();
    
    return $this->qr_code;
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
     * Marquer le billet comme utilisé (scanné à l'entrée)
     */
    public function markAsUsed()
    {
        $this->status = 'used';
        $this->save();
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
     * Obtenir les informations complètes du billet
     */
    public function getFullTicketInfo()
    {
        return [
            'ticket_code' => $this->ticket_code,
            'event_title' => $this->event->title ?? 'N/A',
            'ticket_type' => $this->ticketType->name ?? 'N/A',
            'venue' => $this->event->venue ?? 'N/A',
            'event_date' => $this->event->formatted_event_date ?? 'N/A',
            'event_time' => $this->event->formatted_event_time ?? 'N/A',
            'seat_number' => $this->seat_number,
            'status' => $this->status,
            'price' => $this->ticketType->formatted_price ?? 'N/A'
        ];
    }
}