<?php

namespace App\Services\Stats;

use App\Models\Event;
use App\Models\Ticket;

/**
 * Chiffres réels de la page « Scanner » d'un promoteur (aucune valeur écrite en dur dans la vue) :
 * billets validés aujourd'hui et sur les 7 derniers jours, et billets vendus pas encore scannés pour ses événements à venir.
 *
 * La date de validation est `used_at` ; pour les billets validés avant l'ajout de cette colonne on retombe sur `updated_at`.
 */
class ScannerStats
{
    /** @return array{today:int, week:int, unscanned:int} */
    public function forPromoter(int $promoterId): array
    {
        $used = fn () => Ticket::query()
            ->where('status', 'used')
            ->whereHas('ticketType.event', fn ($q) => $q->where('promoter_id', $promoterId));
        $usedAt = 'COALESCE(tickets.used_at, tickets.updated_at)';

        return [
            'today' => (clone $used())->whereRaw("$usedAt >= ?", [today()->startOfDay()])->count(),
            'week' => (clone $used())->whereRaw("$usedAt >= ?", [now()->subDays(7)])->count(),
            'unscanned' => Ticket::query()
                ->where('status', 'sold')
                ->whereHas('ticketType.event', fn ($q) => $q
                    ->where('promoter_id', $promoterId)
                    ->where('status', 'published')
                    ->where('event_date', '>=', today()))
                ->count(),
        ];
    }
}
