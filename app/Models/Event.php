<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'promoteur_id',
        'category_id',
        'title',
        'description',
        'venue',
        'address',
        'event_date',
        'event_time',
        'end_time',
        'status',
        'image',
        'terms_conditions'
    ];

    protected $casts = [
        'event_date' => 'date',
        'event_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * Relations
     */
    public function promoteur()
    {
        return $this->belongsTo(User::class, 'promoteur_id');
    }

    public function category()
    {
        return $this->belongsTo(EventCategory::class, 'category_id');
    }

    public function ticketTypes()
    {
        return $this->hasMany(TicketType::class);
    }

    public function tickets()
    {
        return $this->hasManyThrough(Ticket::class, TicketType::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function commissions()
    {
        return $this->hasMany(Commission::class, 'promoteur_id', 'promoteur_id');
    }

    /**
     * Scopes
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('event_date', '>=', now()->toDateString());
    }

    public function scopePast($query)
    {
        return $query->where('event_date', '<', now()->toDateString());
    }

    /**
     * Accessors
     */
    public function getFormattedEventDateAttribute()
    {
        return $this->event_date->format('d/m/Y');
    }

    public function getFormattedEventTimeAttribute()
    {
        return $this->event_time->format('H:i');
    }

    public function getIsUpcomingAttribute()
    {
        return $this->event_date >= now()->toDateString();
    }

    public function getIsPastAttribute()
    {
        return $this->event_date < now()->toDateString();
    }

    /**
     * Méthodes de calcul - CORRIGÉES
     */
    public function totalRevenue()
    {
        return $this->orders()->where('payment_status', 'paid')->sum('total_amount') ?? 0;
    }

    public function totalTicketsSold()
    {
        // CORRECTION : Utiliser la relation orderItems pour éviter l'erreur
        return $this->orders()
            ->where('payment_status', 'paid')
            ->withSum('orderItems', 'quantity')
            ->get()
            ->sum('order_items_sum_quantity') ?? 0;
    }

    public function totalTicketsAvailable()
    {
        return $this->ticketTypes()->sum('quantity_available') ?? 0;
    }

    public function availableTicketsCount()
    {
        $total = $this->totalTicketsAvailable();
        $sold = $this->totalTicketsSold();
        return max(0, $total - $sold);
    }

    public function getProgressPercentage()
    {
        $total = $this->totalTicketsAvailable();
        $sold = $this->totalTicketsSold();
        
        return $total > 0 ? round(($sold / $total) * 100, 2) : 0;
    }

    public function isSoldOut()
    {
        return $this->availableTicketsCount() <= 0;
    }

    public function getLowestPrice()
    {
        return $this->ticketTypes()->where('is_active', true)->min('price') ?? 0;
    }

    public function getHighestPrice()
    {
        return $this->ticketTypes()->where('is_active', true)->max('price') ?? 0;
    }

    /**
     * Types de billets disponibles à la vente
     */
    public function availableTicketTypes()
    {
        return $this->ticketTypes()
            ->where('is_active', true)
            ->where('sale_start_date', '<=', now())
            ->where('sale_end_date', '>=', now())
            ->whereRaw('quantity_available > quantity_sold')
            ->get();
    }

    /**
     * Vérifier si l'événement est encore en vente
     */
    public function isOnSale()
    {
        return $this->status === 'published' && 
               $this->isUpcoming && 
               $this->availableTicketTypes()->count() > 0;
    }

    /**
     * Méthode alternative pour calculer les billets vendus (plus simple)
     */
    public function getTicketsSoldCount()
    {
        return $this->ticketTypes()->sum('quantity_sold') ?? 0;
    }

    /**
     * Méthode alternative pour le nombre de commandes
     */
    public function getOrdersCount()
    {
        return $this->orders()->where('payment_status', 'paid')->count() ?? 0;
    }
}