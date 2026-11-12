<?php

namespace App\Http\Controllers\Promoteur;

use App\Http\Controllers\Admin\Concerns\ResolvesStatsPeriod;
use App\Http\Controllers\Controller;
use App\Services\Stats\PromoterStats;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalesController extends Controller
{
    use ResolvesStatsPeriod;

    public function __construct(private readonly PromoterStats $stats) {}

    /**
     * Ventes du promoteur connecté. Cartes, graphique, tableaux par événement et liste des commandes
     * partagent la même période et les mêmes commandes payées (PromoterStats).
     * Les anciennes méthodes `reports` et `export` n'avaient aucune route : supprimées (BOARD R3).
     */
    public function index(Request $request)
    {
        $period = $this->statsPeriod($request, 'promoteur.sales.index');
        $promoterId = (int) Auth::id();

        $summary = $this->stats->overview($promoterId, $period);
        $orders = $this->stats->paidOrdersPage($promoterId, $period);
        $dailySales = $this->stats->salesSeries($promoterId, $period, false);

        // Par événement : ventes de la période, et remplissage cumulé (toutes dates) pour la barre de progression
        $events = collect($this->stats->eventsReport($promoterId, $period))
            ->filter(fn ($e) => $e['gmv'] > 0 || $e['sold'] > 0)->values();
        $fillRates = collect($this->stats->eventsFigures($events->pluck('id')->all()))->map(fn ($f) => $f['fill_rate']);

        return view('promoteur.sales.index', compact('period', 'summary', 'orders', 'dailySales', 'events', 'fillRates'));
    }
}
