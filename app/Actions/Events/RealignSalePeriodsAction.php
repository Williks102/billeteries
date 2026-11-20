<?php

namespace App\Actions\Events;

use App\Models\Event;
use Carbon\CarbonInterface;

/**
 * Recale les fins de vente des billets quand le début de l'événement change.
 * (a) fin == ancien début -> nouveau début ; (b) fin > nouveau début -> nouveau début ;
 * (c) fin antérieure au nouveau début et différente de l'ancien début (choix du promoteur) : inchangée ;
 * (d) début de vente > fin : non modifié, simplement compté (à vérifier par le promoteur).
 * (e) fin déjà passée alors que l'événement est à venir : non modifiée (choix du promoteur possible), mais signalée (`closed`).
 * N'écrit que `sale_end_date` : jamais les colonnes de stock (quantity_*).
 */
class RealignSalePeriodsAction
{
    /** @return array{realigned: int, inconsistent: int, closed: int} */
    public function execute(Event $event, CarbonInterface $oldStart, CarbonInterface $newStart): array
    {
        $realigned = 0;
        $inconsistent = 0;
        $closed = 0;

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
            if ($type->is_active && $end !== null && $end->isPast() && $newStart->isFuture()) {
                $closed++;
            }
        }

        return ['realigned' => $realigned, 'inconsistent' => $inconsistent, 'closed' => $closed];
    }

    /** Phrases à ajouter au message de confirmation après une mise à jour d'événement. */
    public static function notes(array $result): string
    {
        $message = '';
        if (($result['realigned'] ?? 0) > 0) {
            $message .= " Fins de vente recalées pour {$result['realigned']} billet(s).";
        }
        if (($result['inconsistent'] ?? 0) > 0) {
            $message .= " {$result['inconsistent']} billet(s) ont une période de vente incohérente : vérifiez-les.";
        }
        if (($result['closed'] ?? 0) > 0) {
            $message .= " Attention : la vente de {$result['closed']} billet(s) est déjà terminée alors que l'événement est à venir. "
                .'Repoussez leur date de fin de vente pour les remettre en vente.';
        }

        return $message;
    }
}
