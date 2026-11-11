<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Libération du stock des réservations expirées (paiement jamais confirmé)
Schedule::command('orders:release-expired')->everyMinute()->withoutOverlapping();
