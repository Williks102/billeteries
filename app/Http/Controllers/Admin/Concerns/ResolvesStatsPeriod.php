<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Support\Stats\DatePeriod;
use App\Support\Stats\InvalidPeriodException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Lecture unique de la période des écrans de statistiques : DatePeriod::from(Request), défaut = mois civil
 * (Africa/Abidjan). Une période invalide devient une erreur de validation lisible (redirection vers l'écran
 * sans paramètre, ou 422 en JSON) : jamais une 500.
 */
trait ResolvesStatsPeriod
{
    protected function statsPeriod(Request $request, string $fallbackRoute): DatePeriod
    {
        try {
            return DatePeriod::from($request);
        } catch (InvalidPeriodException $e) {
            throw ValidationException::withMessages(['period' => $e->getMessage()])
                ->redirectTo(route($fallbackRoute));
        }
    }
}
