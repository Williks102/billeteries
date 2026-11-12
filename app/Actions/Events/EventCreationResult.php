<?php

namespace App\Actions\Events;

use App\Models\Event;

/** Résultat de CreateEventAction / PublishEventAction. */
final class EventCreationResult
{
    /**
     * @param  array{blocking: array<string,string>, warnings: array<string,string>, can_publish: bool}  $checklist
     */
    public function __construct(
        public readonly Event $event,
        public readonly bool $published,
        public readonly bool $publicationRequested,
        public readonly array $checklist,
    ) {
    }

    /** Publication demandée mais refusée : l'événement est resté en brouillon. */
    public function publicationRefused(): bool
    {
        return $this->publicationRequested && ! $this->published;
    }
}
