<?php

namespace App\Helpers;

/**
 * Durées lisibles en français pour l'affichage (aucune logique métier).
 */
class DurationHelper
{
    /**
     * 2880 -> « 48 heures », 1440 -> « 24 heures », 90 -> « 1 h 30 », 60 -> « 1 heure », 45 -> « 45 minutes ».
     */
    public static function formatMinutes(int $minutes): string
    {
        $minutes = max(0, $minutes);
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        if ($h === 0) {
            return $m.' minute'.($m > 1 ? 's' : '');
        }
        if ($m === 0) {
            return $h.' heure'.($h > 1 ? 's' : '');
        }

        return $h.' h '.str_pad((string) $m, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Durée de réservation annoncée pour un paiement manuel, d'après la configuration.
     * Reproduit CheckoutService::reservationMinutes() (connecté : manual_reservation_minutes ;
     * invité : guest_manual_reservation_minutes).
     */
    public static function reservationMinutes(bool $guest = false): int
    {
        return $guest
            ? (int) config('payments.guest_manual_reservation_minutes', 1440)
            : (int) config('payments.manual_reservation_minutes', 2880);
    }

    public static function reservationLabel(bool $guest = false): string
    {
        return self::formatMinutes(self::reservationMinutes($guest));
    }
}
