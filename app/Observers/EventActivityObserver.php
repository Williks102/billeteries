<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Event;
use App\Services\ActivityLogger;

/**
 * Journal des modifications et suppressions d'événements (toutes voies : admin, promoteur, actions).
 * La création et la duplication sont journalisées par CreateEventAction / DuplicateEventAction.
 * Valeurs métier seulement : jamais de description libre ni de donnée de contact.
 */
class EventActivityObserver
{
    /** Champs suivis (hors statut) => valeurs avant/après journalisées. */
    private const TRACKED = ['title', 'event_date', 'event_time', 'venue', 'address', 'category_id'];

    public function updated(Event $event): void
    {
        $changes = $event->getChanges();

        if (array_key_exists('status', $changes)) {
            $from = $event->getOriginal('status');
            $to = $changes['status'];
            $action = match (true) {
                $to === 'published' => ActivityLog::EVENT_PUBLISHED,
                $from === 'published' && $to === 'draft' => ActivityLog::EVENT_UNPUBLISHED,
                default => ActivityLog::EVENT_STATUS_CHANGED,
            };
            ActivityLogger::record(
                $action,
                $event,
                sprintf('Événement n°%d : statut %s vers %s', $event->id, $from, $to),
                ['status' => ['from' => $from, 'to' => $to]]
            );
        }

        $diff = [];
        foreach (self::TRACKED as $field) {
            if (array_key_exists($field, $changes)) {
                $diff[$field] = ['from' => self::plain($event->getOriginal($field)), 'to' => self::plain($changes[$field])];
            }
        }

        if ($diff !== []) {
            ActivityLogger::record(
                ActivityLog::EVENT_UPDATED,
                $event,
                sprintf('Événement n°%d modifié (%s)', $event->id, implode(', ', array_keys($diff))),
                $diff
            );
        }
    }

    public function deleted(Event $event): void
    {
        ActivityLogger::record(
            ActivityLog::EVENT_DELETED,
            $event,
            sprintf('Événement n°%d supprimé', $event->id),
            ['status' => $event->status],
            $event->id
        );
    }

    private static function plain(mixed $value): mixed
    {
        return $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i') : $value;
    }
}
