<?php

namespace App\Http\Controllers\Acheteur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Stats\BuyerStats;
use Illuminate\Support\Facades\Auth;

class AcheteurController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!Auth::user()->isAcheteur() && !Auth::user()->isAdmin()) {
                abort(403, 'Accès non autorisé');
            }
            return $next($request);
        });
    }
    /**
     * Tableau de bord acheteur : chiffres cumulés (toutes dates) de Scope::buyer, jamais ceux d'un autre.
     */
    public function dashboard(BuyerStats $buyerStats)
    {
        $user = Auth::user();

        $stats = $buyerStats->overview($user->id);
        $recentOrders = $buyerStats->recentOrders($user->id);
        $upcomingEvents = $buyerStats->upcomingOrders($user->id);

        return view('acheteur.dashboard', compact('stats', 'recentOrders', 'upcomingEvents'));
    }
}