<?php

namespace App\Services\Stats;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Périmètre d'une statistique : toute la plateforme, un promoteur, un événement ou un acheteur.
 * Contraint toujours la requête de base (commandes) : un promoteur ne voit JAMAIS les ventes d'un autre.
 */
final class Scope
{
    public const ADMIN = 'admin';

    public const PROMOTER = 'promoter';

    public const EVENT = 'event';

    public const BUYER = 'buyer';

    private function __construct(public readonly string $kind, public readonly ?int $id = null) {}

    public static function admin(): self
    {
        return new self(self::ADMIN);
    }

    public static function promoter(int $promoterId): self
    {
        return new self(self::PROMOTER, $promoterId);
    }

    public static function event(int $eventId): self
    {
        return new self(self::EVENT, $eventId);
    }

    public static function buyer(int $userId): self
    {
        return new self(self::BUYER, $userId);
    }

    public function isBuyer(): bool
    {
        return $this->kind === self::BUYER;
    }

    /** Restreint une requête sur `orders` (Eloquent ou base) à ce périmètre. */
    public function constrainOrders(EloquentBuilder|Builder $query): EloquentBuilder|Builder
    {
        return match ($this->kind) {
            self::ADMIN => $query,
            self::PROMOTER => $query->whereIn('orders.event_id', DB::table('events')->select('id')->where('promoter_id', $this->id)),
            self::EVENT => $query->where('orders.event_id', $this->id),
            self::BUYER => $query->where('orders.user_id', $this->id),
        };
    }

    /**
     * Restreint une requête sur `ticket_types` (capacité). Null si le périmètre n'a pas de notion de capacité
     * (acheteur) : l'appelant renvoie alors null.
     */
    public function constrainTicketTypes(Builder $query): ?Builder
    {
        return match ($this->kind) {
            self::ADMIN => $query,
            self::PROMOTER => $query->whereIn('ticket_types.event_id', DB::table('events')->select('id')->where('promoter_id', $this->id)),
            self::EVENT => $query->where('ticket_types.event_id', $this->id),
            self::BUYER => null,
        };
    }
}
