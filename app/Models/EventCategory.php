<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug', 
        'description',
        'icon'
    ];

    public function getRouteKeyName()
{
    return 'slug';
}

    /**
     * Relation : Une catégorie a plusieurs événements
     */
    public function events()
    {
        return $this->hasMany(Event::class, 'category_id');
    }

    /**
     * Compter les événements actifs dans cette catégorie
     */
    public function activeEventsCount()
    {
        return $this->events()->where('status', 'published')->count();
    }

    /**
     * Scope pour les catégories ayant des événements actifs
     */
    public function scopeWithActiveEvents($query)
{
    return $query->whereHas('events', function($q) {
        $q->where('status', 'published')
          ->where('event_date', '>=', now()->toDateString());
    });
    }
}