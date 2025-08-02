<?php
// app/Models/User.php - VERSION HARMONISÉE

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
        'phone',
        'role',
        'password',
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
     * Relations harmonisées
     */
    public function events()  // ✅ CHANGÉ: Utilise promoter_id
    {
        return $this->hasMany(Event::class, 'promoter_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function commissions()  // ✅ CHANGÉ: Utilise promoter_id
    {
        return $this->hasMany(Commission::class, 'promoter_id');
    }

    public function commissionSettings()  // ✅ CHANGÉ: Utilise promoter_id
    {
        return $this->hasMany(CommissionSetting::class, 'promoter_id');
    }

    public function tickets()
    {
        return $this->hasManyThrough(Ticket::class, Order::class)
            ->join('order_tickets', 'tickets.id', '=', 'order_tickets.ticket_id')
            ->where('orders.payment_status', 'paid');
    }

    /**
     * Statistiques pour promoteur
     */
    public function totalRevenue()
    {
        return $this->commissions()->where('status', 'paid')->sum('net_amount') ?? 0;
    }

    public function pendingRevenue()
    {
        return $this->commissions()->where('status', 'pending')->sum('net_amount') ?? 0;
    }

    public function totalGrossRevenue()
    {
        return $this->commissions()->sum('gross_amount') ?? 0;
    }

    public function totalPlatformCommissions()
    {
        return $this->commissions()->sum('commission_amount') ?? 0;
    }

    public function totalTicketsSold()
    {
        return $this->events()->withCount(['orders' => function ($query) {
            $query->where('payment_status', 'paid');
        }])->get()->sum('orders_count') ?? 0;
    }

    public function activeEventsCount()
    {
        return $this->events()->where('status', 'published')->count() ?? 0;
    }

    public function upcomingEventsCount()
    {
        return $this->events()
            ->where('status', 'published')
            ->where('event_date', '>=', now()->toDateString())
            ->count() ?? 0;
    }
}