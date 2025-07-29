<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Vérification des rôles
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isPromoteur()
    {
        return $this->role === 'promoteur';
    }

    public function isAcheteur()
    {
        return $this->role === 'acheteur';
    }

    /**
     * Relations
     */
    public function events()
    {
        return $this->hasMany(Event::class, 'promoteur_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * CORRECTION: Ajouter la relation commissions manquante
     * Utilise 'promoteur_id' pour correspondre à votre table commissions existante
     */
    public function commissions()
    {
        return $this->hasMany(Commission::class, 'promoteur_id');
    }

    /**
     * Relation : Tickets achetés par l'utilisateur
     */
    public function tickets()
    {
        return $this->hasManyThrough(Ticket::class, Order::class)
            ->join('order_tickets', 'tickets.id', '=', 'order_tickets.ticket_id')
            ->where('orders.payment_status', 'paid');
    }

    /**
     * Statistiques pour promoteur - Version améliorée
     */
    public function totalRevenue()
    {
        // Utilise les commissions payées pour plus de précision
        return $this->commissions()->where('status', 'paid')->sum('net_amount');
    }

    public function pendingRevenue()
    {
        // Utilise les commissions en attente
        return $this->commissions()->where('status', 'pending')->sum('net_amount');
    }

    public function totalTicketsSold()
    {
        return $this->events()->withCount(['orders' => function ($query) {
            $query->where('payment_status', 'paid');
        }])->get()->sum('orders_count');
    }

    /**
     * NOUVELLES MÉTHODES UTILES
     */
    
    /**
     * Revenus totaux générés (brut, avant commission)
     */
    public function totalGrossRevenue()
    {
        return $this->commissions()->sum('gross_amount');
    }

    /**
     * Total des commissions versées à la plateforme
     */
    public function totalPlatformCommissions()
    {
        return $this->commissions()->sum('commission_amount');
    }

    /**
     * Nombre d'événements publiés
     */
    public function publishedEventsCount()
    {
        return $this->events()->where('status', 'published')->count();
    }

    /**
     * Dernier événement créé
     */
    public function latestEvent()
    {
        return $this->events()->latest()->first();
    }

    /**
     * Vérifier si le promoteur a des commissions impayées
     */
    public function hasPendingCommissions()
    {
        return $this->commissions()->where('status', 'pending')->exists();
    }

    /**
     * Commissions du mois en cours
     */
    public function currentMonthCommissions()
    {
        return $this->commissions()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }

    /**
     * Performance du promoteur (taux de conversion approximatif)
     */
    public function getPerformanceStats()
    {
        $totalEvents = $this->events()->count();
        $publishedEvents = $this->publishedEventsCount();
        $eventsWithSales = $this->events()->whereHas('orders', function($query) {
            $query->where('payment_status', 'paid');
        })->count();

        return [
            'total_events' => $totalEvents,
            'published_events' => $publishedEvents,
            'events_with_sales' => $eventsWithSales,
            'publication_rate' => $totalEvents > 0 ? round(($publishedEvents / $totalEvents) * 100, 1) : 0,
            'conversion_rate' => $publishedEvents > 0 ? round(($eventsWithSales / $publishedEvents) * 100, 1) : 0
        ];
    }
}