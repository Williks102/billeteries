<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str; // 🔥 AJOUTER
use Illuminate\Support\Facades\Log; // 🔥 AJOUTER

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'customer_code',
        'is_guest',
        'guest_converted_at',
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
            'is_guest' => 'boolean',
            'guest_converted_at' => 'datetime',
        ];
    }

    /**
     * Lien de réinitialisation : un compte INVITÉ (achat sans compte) reçoit le message d'activation,
     * les autres comptes la notification standard. Le jeton et l'expiration sont ceux du broker.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify($this->is_guest
            ? new \App\Notifications\ActivateGuestAccount($token)
            : new \Illuminate\Auth\Notifications\ResetPassword($token));
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

    /**
 * Générer un code client unique
 */
public static function generateCustomerCode($role = 'acheteur')
{
    $prefix = match($role) {
        'admin' => 'ADM',
        'promoteur' => 'PRO', 
        'acheteur' => 'CLI',
        default => 'USR'
    };

    do {
        // Format: CLI-250901-A7B2 (Préfixe-AAMMJJ-Code4)
        $code = $prefix . '-' . now()->format('ymd') . '-' . strtoupper(Str::random(4));
    } while (self::where('customer_code', $code)->exists());
    
    return $code;
}

/**
 * Assigner un code client automatiquement
 */
public function assignCustomerCode()
{
    if (!$this->customer_code) {
        $this->customer_code = self::generateCustomerCode($this->role);
        $this->save();
        
        \Log::info("Code client assigné", [
            'user_id' => $this->id,
            'customer_code' => $this->customer_code,
            'role' => $this->role
        ]);
    }
    
    return $this->customer_code;
}

/**
 * Event automatique : Assigner code après création
 */
protected static function boot()
{
    parent::boot();

    // Assigner automatiquement un code client après création
    static::created(function ($user) {
        if (!$user->customer_code) {
            $user->assignCustomerCode();
        }
    });
}

/**
 * Rechercher par code client
 */
public static function findByCustomerCode($code)
{
    return self::where('customer_code', strtoupper($code))->first();
}
}
