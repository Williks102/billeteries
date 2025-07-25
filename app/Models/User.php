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
        'phone',
        'role',
        'password',
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

    // Dans le modèle User
public function totalRevenue()
{
    return $this->events()
        ->whereHas('orders', function($query) {
            $query->where('payment_status', 'paid');
        })
        ->with('orders')
        ->get()
        ->sum(function($event) {
            return $event->orders->sum('total_amount');
        });
}

public function pendingRevenue()
{
    return $this->events()
        ->whereHas('orders', function($query) {
            $query->where('payment_status', 'pending');
        })
        ->with('orders')
        ->get()
        ->sum(function($event) {
            return $event->orders->sum('total_amount');
        });
}
}