<?php

namespace App\Support\Events;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Règles uniques de la période de vente d'un billet (création d'événement, ajout/édition de billet).
 *
 * - début de vente : la date saisie (00:00) ou, par défaut, maintenant ;
 * - fin de vente : la date saisie (23:59:59) ou, par défaut, le DÉBUT DE L'ÉVÉNEMENT (date + heure) ;
 *   la fin n'est jamais postérieure au début de l'événement (une date de fin = jour de l'événement
 *   est ramenée à l'heure de début) ;
 * - cohérence exigée : début <= fin <= début de l'événement.
 */
final class SalePeriod
{
    /** Début de l'événement (date + heure de début) ; sans heure : début de journée. */
    public static function eventStart(string $date, ?string $time): Carbon
    {
        $time = ($time !== null && preg_match('/^\d{2}:\d{2}/', $time)) ? substr($time, 0, 5) : '00:00';

        return Carbon::createFromFormat('Y-m-d H:i', substr($date, 0, 10).' '.$time);
    }

    /**
     * @return array{0: Carbon, 1: Carbon} [début de vente, fin de vente]
     */
    public static function resolve(CarbonInterface $eventStart, ?string $startDate, ?string $endDate, ?CarbonInterface $now = null): array
    {
        $now = $now ? Carbon::instance($now) : Carbon::now();
        $eventStart = Carbon::instance($eventStart);

        $end = self::endFromDate($eventStart, $endDate);
        $start = ($startDate !== null && $startDate !== '')
            ? Carbon::parse($startDate)->startOfDay()
            : ($now->greaterThan($end) ? $end->copy() : $now->copy());

        return [$start, $end];
    }

    /** Fin de vente à partir d'une date saisie (null/'' = début de l'événement), bornée par le début de l'événement. */
    public static function endFromDate(CarbonInterface $eventStart, ?string $endDate): Carbon
    {
        $eventStart = Carbon::instance($eventStart);
        if ($endDate === null || $endDate === '') {
            return $eventStart->copy();
        }

        $end = Carbon::parse($endDate)->endOfDay();

        return $end->isSameDay($eventStart) ? $eventStart->copy() : $end;
    }

    /**
     * Erreur de cohérence éventuelle (message français) ou null.
     * Retourne [champ, message] avec champ = 'sale_start_date' | 'sale_end_date'.
     *
     * @return array{0: string, 1: string}|null
     */
    public static function violation(CarbonInterface $eventStart, ?string $startDate, ?string $endDate): ?array
    {
        $eventStart = Carbon::instance($eventStart);

        if ($endDate !== null && $endDate !== '' && Carbon::parse($endDate)->startOfDay()->greaterThan($eventStart->copy()->startOfDay())) {
            return ['sale_end_date', "La fin de vente ne peut pas être après le début de l'événement."];
        }

        [$start, $end] = self::resolve($eventStart, $startDate, $endDate);
        if ($startDate !== null && $startDate !== '' && $start->greaterThan($end)) {
            return ['sale_end_date', 'La fin de vente doit être après le début de la vente.'];
        }

        return null;
    }
}
