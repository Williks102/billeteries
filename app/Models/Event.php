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
    
    public function promoteur()
{
    return $this->belongsTo(User::class, 'promoter_id'); // ✅ Votre DB utilise promoter_id
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
 * Méthodes existantes mais améliorées pour éviter les erreurs
 */
public function totalRevenue()
{
    try {
        return $this->orders()
            ->where('payment_status', 'paid')
            ->sum('total_amount') ?? 0;
    } catch (\Exception $e) {
        \Log::error('Erreur calcul revenue pour event ' . $this->id . ': ' . $e->getMessage());
        return 0;
    }
}

public function getTicketsSoldCount()
{
    try {
        return $this->tickets()
            ->whereIn('status', ['sold', 'used'])
            ->count() ?? 0;
    } catch (\Exception $e) {
        \Log::error('Erreur calcul tickets vendus pour event ' . $this->id . ': ' . $e->getMessage());
        return 0;
    }
}

   
    public function availableTicketsCount()
    {
        return $this->totalTicketsAvailable() - $this->totalTicketsSold();
    }

    public function getOrdersCount()
{
    try {
        return $this->orders()
            ->where('payment_status', 'paid')
            ->count() ?? 0;
    } catch (\Exception $e) {
        \Log::error('Erreur calcul commandes pour event ' . $this->id . ': ' . $e->getMessage());
        return 0;
    }
}
    public function totalTicketsAvailable()
{
    try {
        return $this->ticketTypes()
            ->sum('quantity_available') ?? 0;
    } catch (\Exception $e) {
        \Log::error('Erreur calcul tickets disponibles pour event ' . $this->id . ': ' . $e->getMessage());
        return 0;
    }
}

    
public function getProgressPercentage()
{
    try {
        $total = $this->totalTicketsAvailable();
        $sold = $this->getTicketsSoldCount();
        
        return $total > 0 ? round(($sold / $total) * 100, 2) : 0;
    } catch (\Exception $e) {
        \Log::error('Erreur calcul pourcentage pour event ' . $this->id . ': ' . $e->getMessage());
        return 0;
    }
}
    /**
 * Méthode manquante : getCommissionEarned()
 */
public function getCommissionEarned()
{
    try {
        // ✅ OPTION 1: Via les commandes liées à cet événement
        return \App\Models\Commission::whereHas('order', function($query) {
                $query->where('event_id', $this->id);
            })
            ->where('status', 'paid')
            ->sum('commission_amount') ?? 0;
            
    } catch (\Exception $e) {
        \Log::error('Erreur calcul commission pour event ' . $this->id . ': ' . $e->getMessage());
        
        // ✅ OPTION 2: Fallback - Calcul basé sur le total des commandes
        try {
            $totalRevenue = $this->totalRevenue();
            $commissionRate = 0.05; // 5% par défaut, ajustez selon vos règles
            return $totalRevenue * $commissionRate;
            
        } catch (\Exception $e2) {
            return 0;
        }
    }
}

    
}