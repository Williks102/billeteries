<?php

namespace App\Http\Controllers\Promoteur;

use App\Http\Controllers\Admin\Concerns\ResolvesStatsPeriod;
use App\Http\Controllers\Controller;
use App\Services\Stats\PromoterStats;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportsController extends Controller
{
    use ResolvesStatsPeriod;

    private const TYPES = ['overview', 'events', 'financial'];

    public function __construct(private readonly PromoterStats $stats) {}

    /**
     * Rapports du promoteur connecté (vue d'ensemble, par événement, financier). Un seul calcul des chiffres :
     * PromoterStats (commandes payées, GMV = somme des lignes : une commande à plusieurs lignes compte une fois).
     */
    public function index(Request $request)
    {
        $type = $request->route()?->parameter('type') ?? $request->query('type', 'overview');
        $type = in_array($type, self::TYPES, true) ? $type : 'overview';

        $period = $this->statsPeriod($request, $request->route()?->getName() ?? 'promoteur.reports.index');
        $promoterId = (int) Auth::id();

        $stats = $this->stats->overview($promoterId, $period);

        $data = match ($type) {
            'events' => ['events' => $this->stats->eventsReport($promoterId, $period)],
            'financial' => ['months' => $this->stats->monthlyFinancials($promoterId, $period)],
            default => [
                'series' => $this->stats->salesSeries($promoterId, $period),
                'recent_orders' => $this->stats->recentPaidOrders($promoterId, $period, 10),
            ],
        };

        return view('promoteur.reports.index', compact('stats', 'data', 'type', 'period'));
    }

    /**
     * Export des données (route conservée ; l'export réel relève du lot L7).
     */
    public function export(Request $request)
    {
        return back()->with('info', 'Export en cours de développement');
    }
}
