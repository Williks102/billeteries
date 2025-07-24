<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketType extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'name',
        'description',
        'price',
        'quantity_available',
        'quantity_sold',
        'sale_start_date',
        'sale_end_date',
        'max_per_order',
        'is_active'
    ];

    protected $casts = [
        'sale_start_date' => 'datetime',
        'sale_end_date' => 'datetime',
        'is_active' => 'boolean',
        'price' => 'integer', // Prix en FCFA (entier)
    ];

    /**
     * Relations
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Accessors - Formatage prix FCFA
     */
    public function getFormattedPriceAttribute()
    {
        return number_format($this->price, 0, ',', ' ') . ' FCFA';
    }

    /**
     * Calculs de disponibilité
     */
    public function remainingTickets()
    {
        return $this->quantity_available - $this->quantity_sold;
    }

    public function getProgressPercentage()
    {
        return $this->quantity_available > 0 
            ? round(($this->quantity_sold / $this->quantity_available) * 100, 2) 
            : 0;
    }

    public function isSoldOut()
    {
        return $this->remainingTickets() <= 0;
    }

    /**
     * Vérifications de disponibilité
     */
    public function isAvailable()
    {
        return $this->is_active && 
               $this->remainingTickets() > 0 &&
               now()->between($this->sale_start_date, $this->sale_end_date);
    }

    public function isSaleActive()
    {
        return now()->between($this->sale_start_date, $this->sale_end_date);
    }

    public function isSaleStarted()
    {
        return now()->gte($this->sale_start_date);
    }

    public function isSaleEnded()
    {
        return now()->gt($this->sale_end_date);
    }

    /**
     * Vérifier si une quantité est disponible
     */
    public function canPurchaseQuantity($quantity)
    {
        return $this->isAvailable() && 
               $quantity <= $this->remainingTickets() && 
               $quantity <= $this->max_per_order;
    }

    /**
     * Calculer le montant total pour une quantité
     */
    public function getTotalPrice($quantity)
    {
        return $this->price * $quantity;
    }

    /**
     * Scope pour les types actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)
                    ->where('sale_start_date', '<=', now())
                    ->where('sale_end_date', '>=', now())
                    ->whereRaw('quantity_available > quantity_sold');
    }

    /**
     * Méthodes de gestion du stock
     */
    public function reserveTickets($quantity)
    {
        if ($this->canPurchaseQuantity($quantity)) {
            $this->increment('quantity_sold', $quantity);
            return true;
        }
        return false;
    }

    public function releaseTickets($quantity)
    {
        $this->decrement('quantity_sold', $quantity);
    }
}