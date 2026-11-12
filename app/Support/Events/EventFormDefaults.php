<?php

namespace App\Support\Events;

use Carbon\Carbon;

/** Valeurs proposées par le formulaire de création d'événement (promoteur, puis admin). */
final class EventFormDefaults
{
    public const TIME = '20:00';
    public const QUANTITY = 100;
    public const MAX_PER_ORDER = 10;

    /** @return array{event_date: string, event_time: string, quantity_available: int, max_per_order: int, ticket_name: string} */
    public static function values(?Carbon $now = null): array
    {
        $now = $now ?? Carbon::now();

        return [
            'event_date' => $now->copy()->next(Carbon::SATURDAY)->format('Y-m-d'),
            'event_time' => self::TIME,
            'quantity_available' => self::QUANTITY,
            'max_per_order' => self::MAX_PER_ORDER,
            'ticket_name' => \App\Http\Requests\StoreEventRequest::DEFAULT_TICKET_NAME,
        ];
    }
}
