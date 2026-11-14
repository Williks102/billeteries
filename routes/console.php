<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Libération du stock des réservations expirées (paiement jamais confirmé)
Schedule::command('orders:release-expired')->everyMinute()->withoutOverlapping();

// Confidentialité : failed_jobs conserve le texte des exceptions (qui peut citer un identifiant SMTP) -> purge à 72 h
Schedule::command('queue:prune-failed --hours=72')->dailyAt('03:30');
