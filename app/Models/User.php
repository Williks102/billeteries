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
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relation : Un promoteur a plusieurs événements
     */
    public function events()
    {
        return $this->hasMany(Event::class, 'promoteur_id');
    }

    /**
     * Relation : Un utilisateur a plusieurs commandes
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Relation : Un promoteur a plusieurs commissions
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
     * Statistiques pour promoteur
     */
    public function totalRevenue()
    {
        return $this->commissions()->where('status', 'paid')->sum('net_amount');
    }

    public function pendingRevenue()
    {
        return $this->commissions()->where('status', 'pending')->sum('net_amount');
    }

    public function totalTicketsSold()
    {
        return $this->events()->withCount(['orders' => function ($query) {
            $query->where('payment_status', 'paid');
        }])->get()->sum('orders_count');
    }
}