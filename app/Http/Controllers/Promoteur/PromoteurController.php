<?php

namespace App\Http\Controllers\Promoteur;

use App\Http\Controllers\Admin\Concerns\ResolvesStatsPeriod;
use App\Http\Controllers\Controller;
use App\Services\Stats\PromoterStats;
use App\Services\Stats\Scope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PromoteurController extends Controller
{
    use ResolvesStatsPeriod;

    public function __construct(private readonly PromoterStats $stats) {}

    /**
     * Tableau de bord du promoteur connecté : tous les chiffres viennent de PromoterStats (périmètre = ses
     * événements uniquement, période par défaut = mois civil en cours). La vue n'exécute aucune requête.
     */
    public function dashboard(Request $request)
    {
        $period = $this->statsPeriod($request, 'promoteur.dashboard');
        $promoterId = (int) Auth::id();

        $summary = $this->stats->overview($promoterId, $period);
        $recentOrders = $this->stats->recentPaidOrders($promoterId, $period, 5);

        $recentEvents = Auth::user()->events()->with('category')->latest()->take(5)->get();
        $eventStats = $this->stats->eventsFigures($recentEvents->pluck('id')->all());

        return view('promoteur.dashboard', compact('period', 'summary', 'recentOrders', 'recentEvents', 'eventStats'));
    }
}
