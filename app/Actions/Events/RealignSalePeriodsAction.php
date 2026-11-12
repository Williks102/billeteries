<?php

namespace App\Actions\Events;

use App\Models\Event;
use Carbon\CarbonInterface;

/**
 * Recale les fins de vente des billets quand le début de l'événement change.
 * (a) fin == ancien début -> nouveau début ; (b) fin > nouveau début -> nouveau début ;
 * (c) fin antérieure au nouveau début et différente de l'ancien début (choix du promoteur) : inchangée ;
 * (d) début de vente > fin : non modifié, simplement compté (à vérifier par le promoteur).
 * N'écrit que `sale_end_date` : jamais les colonnes de stock (quantity_*).
 */
class RealignSalePeriodsAction
{
    /** @return array{realigned: int, inconsistent: int} */
    public function execute(Event $event, CarbonInterface $oldStart, CarbonInterface $newStart): array
    {
        $realigned = 0;
        $inconsistent = 0;

        foreach ($event->ticketTypes()->get() as $type) {
            $end = $type->sale_end_date;
            if ($end !== null && ($end->equalTo($oldStart) || $end->greaterThan($newStart)) && ! $end->equalTo($newStart)) {
                $type->sale_end_date = $newStart->copy();
                $type->save();
                $realigned++;
                $end = $type->sale_end_date;
            }
            if ($end !== null && $type->sale_start_date !== null && $type->sale_start_date->greaterThan($end)) {
                $inconsistent++;
            }
        }

        return ['realigned' => $realigned, 'inconsistent' => $inconsistent];
    }
}
