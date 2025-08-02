<?php
// app/Models/Event.php - VERSION HARMONISÉE

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'promoter_id',  // ✅ CHANGÉ: promoteur_id → promoter_id
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
    public function promoter()  // ✅ CHANGÉ: Nouvelle relation principale
    {
        return $this->belongsTo(User::class, 'promoter_id');
    }
    
    public function promoteur()  // ✅ ALIAS: Pour compatibilité
    {
        return $this->promoter();
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

    public function commissions()  // ✅ CHANGÉ: Utilise promoter_id
    {
        return $this->hasMany(Commission::class, 'promoter_id', 'promoter_id');
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

    public function scopeForPromoter($query, $promoterId)  // ✅ NOUVEAU: Scope pratique
    {
        return $query->where('promoter_id', $promoterId);
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
     * Méthodes de calcul
     */
    public function totalRevenue()
    {
        return $this->orders()->where('payment_status', 'paid')->sum('total_amount') ?? 0;
    }

    public function totalTicketsSold()
    {
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
        return $this->totalTicketsAvailable() - $this->totalTicketsSold();
    }

    public function getProgressPercentage()
    {
        $total = $this->totalTicketsAvailable();
        $sold = $this->totalTicketsSold();
        
        return $total > 0 ? round(($sold / $total) * 100, 2) : 0;
    }

    // Méthodes alternatives pour éviter les erreurs de relations
    public function getTicketsSoldCount()
    {
        return $this->ticketTypes()->sum('quantity_sold') ?? 0;
    }

    public function getOrdersCount()
    {
        return $this->orders()->where('payment_status', 'paid')->count() ?? 0;
    }
}