<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * ✅ CORRIGÉ: Relation avec events (promoter_id au lieu de promoteur_id)
     */
    public function events()
    {
        return $this->hasMany(Event::class, 'promoter_id');
    }

    /**
     * Relation : Un utilisateur a plusieurs commandes
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * ✅ CORRIGÉ: Relation avec commissions (promoter_id au lieu de promoteur_id)
     */
    public function commissions()
    {
        return $this->hasMany(Commission::class, 'promoter_id');
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
     * Vérifications de rôles
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
     * ✅ CORRIGÉ: Statistiques pour promoteur basées sur les ventes réelles
     */
    public function totalRevenue()
    {
        try {
            return $this->events()
                ->join('orders', 'events.id', '=', 'orders.event_id')
                ->where('orders.payment_status', 'paid')
                ->sum('orders.total_amount') ?? 0;
        } catch (\Exception $e) {
            \Log::error('Erreur calcul totalRevenue: ' . $e->getMessage());
            return 0;
        }
    }

    public function pendingRevenue()
    {
        try {
            return $this->commissions()
                ->where('status', 'pending')
                ->sum('net_amount') ?? 0;
        } catch (\Exception $e) {
            \Log::error('Erreur calcul pendingRevenue: ' . $e->getMessage());
            return 0;
        }
    }

    public function totalTicketsSold()
    {
        try {
            return $this->events()
                ->join('orders', 'events.id', '=', 'orders.event_id')
                ->join('order_items', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.payment_status', 'paid')
                ->sum('order_items.quantity') ?? 0;
        } catch (\Exception $e) {
            \Log::error('Erreur calcul totalTicketsSold: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * ✅ NOUVEAU: Statistiques des ventes pour l'onglet "Ventes"
     */
    public function getSalesStats($startDate = null, $endDate = null)
    {
        $query = $this->events()
            ->join('orders', 'events.id', '=', 'orders.event_id')
            ->where('orders.payment_status', 'paid');

        if ($startDate) {
            $query->where('orders.created_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('orders.created_at', '<=', $endDate);
        }

        return [
            'total_revenue' => $query->sum('orders.total_amount') ?? 0,
            'total_orders' => $query->count() ?? 0,
            'total_tickets' => $query->join('order_items', 'orders.id', '=', 'order_items.order_id')
                                    ->sum('order_items.quantity') ?? 0,
        ];
    }
}