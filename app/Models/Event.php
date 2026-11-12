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

    /*
     * Statistiques : délèguent aux services App\Services\Stats (définitions uniques, voir docs/agents/decisions.md).
     * Signatures publiques conservées (appelées par les vues admin).
     */

    /** Ventes (GMV, hors frais) de l'événement, toutes dates : somme des lignes des commandes payées. */
    public function totalRevenue()
    {
        return app(\App\Services\Stats\SalesStats::class)->gmv(\App\Services\Stats\Scope::event($this->id), \App\Support\Stats\DatePeriod::all());
    }

    /** Billets vendus = sold + used d'une commande payée. */
    public function getTicketsSoldCount()
    {
        return app(\App\Services\Stats\TicketStats::class)->sold(\App\Services\Stats\Scope::event($this->id));
    }

    /** Places disponibles = capacité - vendus (les réservations non payées restent disponibles). */
    public function availableTicketsCount()
    {
        return app(\App\Services\Stats\TicketStats::class)->available(\App\Services\Stats\Scope::event($this->id)) ?? 0;
    }

    /** Commandes PAYÉES de l'événement. */
    public function getOrdersCount()
    {
        return app(\App\Services\Stats\SalesStats::class)->paidOrdersCount(\App\Services\Stats\Scope::event($this->id), \App\Support\Stats\DatePeriod::all());
    }

    /** Capacité = somme des quantités mises en vente. */
    public function totalTicketsAvailable()
    {
        return app(\App\Services\Stats\TicketStats::class)->capacity(\App\Services\Stats\Scope::event($this->id)) ?? 0;
    }

    /** Taux de remplissage (%) = vendus / capacité, 1 décimale (0 sans capacité). */
    public function getProgressPercentage()
    {
        return app(\App\Services\Stats\TicketStats::class)->fillRate(\App\Services\Stats\Scope::event($this->id)) ?? 0;
    }

    /** Commission de la plateforme sur les commandes payées de l'événement, toutes dates. */
    public function getCommissionEarned()
    {
        return app(\App\Services\Stats\CommissionStats::class)->platformRevenue(\App\Services\Stats\Scope::event($this->id), \App\Support\Stats\DatePeriod::all());
    }
}