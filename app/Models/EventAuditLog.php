<?php
// app/Models/EventAuditLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'action',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relations
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Accesseurs pour l'affichage
    public function getActionLabelAttribute(): string
    {
        return match($this->action) {
            'created' => 'Événement créé',
            'updated' => 'Événement modifié',
            'management_mode_changed' => 'Mode de gestion modifié',
            'tickets_created' => 'Billets créés',
            'tickets_updated' => 'Billets modifiés',
            'prices_updated' => 'Prix modifiés',
            'published' => 'Événement publié',
            'unpublished' => 'Événement dépublié',
            'admin_takeover' => 'Prise de contrôle admin',
            'promoter_restored' => 'Contrôle rendu au promoteur',
            default => ucfirst($this->action)
        };
    }

    public function getActionColorAttribute(): string
    {
        return match($this->action) {
            'created' => 'success',
            'updated' => 'info',
            'management_mode_changed' => 'warning',
            'tickets_created', 'tickets_updated' => 'primary',
            'prices_updated' => 'warning',
            'published' => 'success',
            'unpublished' => 'secondary',
            'admin_takeover' => 'danger',
            'promoter_restored' => 'success',
            default => 'secondary'
        };
    }

    public function getActionIconAttribute(): string
    {
        return match($this->action) {
            'created' => 'fas fa-plus-circle',
            'updated' => 'fas fa-edit',
            'management_mode_changed' => 'fas fa-exchange-alt',
            'tickets_created' => 'fas fa-ticket-alt',
            'tickets_updated' => 'fas fa-tickets',
            'prices_updated' => 'fas fa-dollar-sign',
            'published' => 'fas fa-eye',
            'unpublished' => 'fas fa-eye-slash',
            'admin_takeover' => 'fas fa-user-shield',
            'promoter_restored' => 'fas fa-user-check',
            default => 'fas fa-info-circle'
        };
    }

    // Scopes
    public function scopeForEvent($query, $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeManagementChanges($query)
    {
        return $query->whereIn('action', [
            'admin_takeover', 
            'promoter_restored', 
            'management_mode_changed'
        ]);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}