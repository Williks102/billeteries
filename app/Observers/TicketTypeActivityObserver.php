<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\TicketType;
use App\Services\ActivityLogger;

/**
 * Journal des types de billets : création, modification du prix, de la capacité, de l'activation ou des
 * dates de vente (avant/après), suppression. Les mouvements de `quantity_sold` (ventes) ne sont PAS journalisés :
 * ils sont déjà tracés par les commandes et leur volume noierait le journal.
 */
class TicketTypeActivityObserver
{
    private const TRACKED = ['price', 'quantity_available', 'is_active', 'sale_start_date', 'sale_end_date', 'max_per_order'];

    public function created(TicketType $type): void
    {
        ActivityLogger::record(
            ActivityLog::TICKET_TYPE_CREATED,
            $type,
            sprintf('Billet n°%d créé pour l\'événement n°%d', $type->id, $type->event_id),
            [
                'price' => (int) $type->price,
                'quantity_available' => (int) $type->quantity_available,
                'is_active' => (bool) $type->is_active,
            ],
            (int) $type->event_id
        );
    }

    public function updated(TicketType $type): void
    {
        $changes = $type->getChanges();
        $diff = [];
        foreach (self::TRACKED as $field) {
            if (array_key_exists($field, $changes)) {
                $diff[$field] = ['from' => self::plain($type->getOriginal($field)), 'to' => self::plain($type->getAttribute($field))];
            }
        }

        if ($diff !== []) {
            ActivityLogger::record(
                ActivityLog::TICKET_TYPE_UPDATED,
                $type,
                sprintf('Billet n°%d modifié (%s)', $type->id, implode(', ', array_keys($diff))),
                $diff,
                (int) $type->event_id
            );
        }
    }

    public function deleted(TicketType $type): void
    {
        ActivityLogger::record(
            ActivityLog::TICKET_TYPE_DELETED,
            $type,
            sprintf('Billet n°%d supprimé', $type->id),
            ['price' => (int) $type->price, 'quantity_available' => (int) $type->quantity_available, 'quantity_sold' => (int) $type->quantity_sold],
            (int) $type->event_id
        );
    }

    private static function plain(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i');
        }

        return is_bool($value) ? $value : (is_numeric($value) ? (int) $value : $value);
    }
}
