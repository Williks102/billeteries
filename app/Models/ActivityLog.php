<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Journal d'activité : APPEND-ONLY. Aucune modification ni suppression applicative (les méthodes lèvent).
 * Écrire via App\Services\ActivityLogger uniquement ; ne jamais y mettre de donnée personnelle.
 */
class ActivityLog extends Model
{
    public $timestamps = false;

    public const UPDATED_AT = null;

    // Paiement
    public const PAYMENT_CONFIRMED = 'payment.confirmed';

    // Commande
    public const ORDER_STATUS_CHANGED = 'order.status_changed';
    public const ORDER_CANCELLED = 'order.cancelled';
    public const ORDER_REFUNDED = 'order.refunded';
    public const ORDER_EXPIRED = 'order.expired';

    // Billets (types de billets : prix, stock, vente)
    public const TICKET_TYPE_CREATED = 'ticket_type.created';
    public const TICKET_TYPE_UPDATED = 'ticket_type.updated';
    public const TICKET_TYPE_DELETED = 'ticket_type.deleted';

    // Événements
    public const EVENT_CREATED = 'event.created';
    public const EVENT_UPDATED = 'event.updated';
    public const EVENT_PUBLISHED = 'event.published';
    public const EVENT_UNPUBLISHED = 'event.unpublished';
    public const EVENT_STATUS_CHANGED = 'event.status_changed';
    public const EVENT_DUPLICATED = 'event.duplicated';
    public const EVENT_DELETED = 'event.deleted';

    // Plateforme et utilisateurs
    public const SETTINGS_UPDATED = 'settings.updated';
    public const USER_CREATED = 'user.created';
    public const USER_DELETED = 'user.deleted';
    public const USER_ROLE_CHANGED = 'user.role_changed';
    public const USER_STATUS_CHANGED = 'user.status_changed';
    public const AUTH_ADMIN_LOGIN = 'auth.admin_login';

    /** Libellés français affichés dans le journal (clé stable => texte). */
    public const LABELS = [
        self::PAYMENT_CONFIRMED => 'Paiement confirmé',
        self::ORDER_STATUS_CHANGED => 'Statut de commande modifié',
        self::ORDER_CANCELLED => 'Commande annulée',
        self::ORDER_REFUNDED => 'Commande remboursée',
        self::ORDER_EXPIRED => 'Réservation expirée',
        self::TICKET_TYPE_CREATED => 'Billet créé',
        self::TICKET_TYPE_UPDATED => 'Billet modifié',
        self::TICKET_TYPE_DELETED => 'Billet supprimé',
        self::EVENT_CREATED => 'Événement créé',
        self::EVENT_UPDATED => 'Événement modifié',
        self::EVENT_PUBLISHED => 'Événement publié',
        self::EVENT_UNPUBLISHED => 'Événement dépublié',
        self::EVENT_STATUS_CHANGED => "Statut d'événement modifié",
        self::EVENT_DUPLICATED => 'Événement dupliqué',
        self::EVENT_DELETED => 'Événement supprimé',
        self::SETTINGS_UPDATED => 'Paramètres modifiés',
        self::USER_CREATED => 'Utilisateur créé',
        self::USER_DELETED => 'Utilisateur supprimé',
        self::USER_ROLE_CHANGED => "Rôle d'utilisateur modifié",
        self::USER_STATUS_CHANGED => "Statut d'utilisateur modifié",
        self::AUTH_ADMIN_LOGIN => 'Connexion administrateur',
    ];

    /** Familles du filtre : clé => [libellé, préfixes d'action]. */
    public const FAMILIES = [
        'payment' => ['Paiement', ['payment.']],
        'order' => ['Commande', ['order.']],
        'ticket' => ['Billet', ['ticket_type.', 'ticket.']],
        'event' => ['Événement', ['event.']],
        'platform' => ['Plateforme', ['settings.', 'auth.']],
        'user' => ['Utilisateur', ['user.']],
    ];

    protected $fillable = [
        'user_id', 'actor_role', 'action', 'subject_type', 'subject_id', 'event_id',
        'description', 'properties', 'ip_fingerprint', 'created_at',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException("Le journal d'activité est en ajout seul : modification interdite.");
        });
        static::deleting(function () {
            throw new LogicException("Le journal d'activité est en ajout seul : suppression interdite.");
        });
    }

    public function update(array $attributes = [], array $options = [])
    {
        throw new LogicException("Le journal d'activité est en ajout seul : modification interdite.");
    }

    public function delete()
    {
        throw new LogicException("Le journal d'activité est en ajout seul : suppression interdite.");
    }

    public function forceDelete()
    {
        throw new LogicException("Le journal d'activité est en ajout seul : suppression interdite.");
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function label(): string
    {
        return self::LABELS[$this->action] ?? $this->action;
    }

    /** Filtre par famille d'actions (clé de FAMILIES) ; clé inconnue = aucun filtre. */
    public function scopeOfFamily(Builder $query, ?string $family): Builder
    {
        if ($family === null || ! isset(self::FAMILIES[$family])) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($family) {
            foreach (self::FAMILIES[$family][1] as $prefix) {
                $q->orWhere('action', 'like', $prefix.'%');
            }
        });
    }
}
