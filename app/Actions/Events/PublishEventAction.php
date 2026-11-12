<?php

namespace App\Actions\Events;

use App\Models\Event;
use Illuminate\Support\Facades\DB;

/**
 * Publie un événement UNIQUEMENT si la checklist de publication n'a aucun point bloquant.
 * Sous verrou : la checklist est évaluée sur l'état courant de l'événement.
 * Ne touche ni aux commissions, ni aux commandes, ni au stock.
 */
class PublishEventAction
{
    public function execute(Event $event): EventCreationResult
    {
        return DB::transaction(function () use ($event) {
            $locked = Event::query()->whereKey($event->getKey())->lockForUpdate()->firstOrFail();
            $checklist = $locked->publicationChecklist();

            if ($checklist['can_publish'] && $locked->status !== 'published') {
                $locked->update(['status' => 'published']);
            }

            return new EventCreationResult($locked, $locked->status === 'published' && $checklist['can_publish'], true, $checklist);
        });
    }
}
