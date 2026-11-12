<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

    /** Places restantes AFFICHÉES AU PUBLIC (D6) : capacité - quantity_sold, jamais négatif (voir PublicStats). */
    public function publicRemaining(): int
    {
        return app(\App\Services\Stats\PublicStats::class)->remainingForType($this);
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
               $this->eventAcceptsSales() &&
               $this->remainingTickets() > 0 &&
               now()->between($this->sale_start_date, $this->sale_end_date);
    }

    /**
     * Un billet n'est vendable que pour un événement publié et pas encore passé (sécurité : un identifiant
     * de billet deviné ne doit pas permettre de réserver du stock d'un brouillon, d'un événement annulé ou passé).
     */
    public function eventAcceptsSales(): bool
    {
        $event = $this->event;

        return $event !== null
            && $event->status === 'published'
            && ($event->event_date === null || $event->event_date->toDateString() >= now()->toDateString());
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
    /**
     * Réservation atomique : verrou de ligne + revérification de disponibilité sous verrou.
     * Seul point d'incrément de quantity_sold (ne pas ré-incrémenter côté appelant).
     */
    public function reserveTickets($quantity): bool
    {
        $quantity = (int) $quantity;
        if ($quantity <= 0) {
            return false;
        }

        return DB::transaction(function () use ($quantity) {
            $fresh = static::whereKey($this->getKey())->lockForUpdate()->first();

            if (!$fresh || !$fresh->canPurchaseQuantity($quantity)) {
                return false;
            }

            $fresh->increment('quantity_sold', $quantity);
            $this->quantity_sold = $fresh->quantity_sold;
            $this->syncOriginalAttribute('quantity_sold');

            return true;
        });
    }

    /**
     * Libération de stock (ne descend jamais sous 0). À appeler sous transaction.
     */
    public function releaseTickets($quantity): void
    {
        $quantity = (int) $quantity;
        if ($quantity <= 0) {
            return;
        }

        static::whereKey($this->getKey())
            ->where('quantity_sold', '>=', $quantity)
            ->decrement('quantity_sold', $quantity);

        $this->refresh();
    }
}
