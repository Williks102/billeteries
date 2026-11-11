<?php

namespace App\Http\Controllers\Acheteur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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
 * Dashboard acheteur - VERSION CORRIGÉE
 */
public function dashboard()
{
    $user = Auth::user();
    
    // Statistiques corrigées
    $totalOrders = $user->orders()->visibleInBuyerHistory()->count();
    $paidOrders = $user->orders()->where('payment_status', 'paid');
    
    // Calculer le nombre total de billets
    $totalTickets = 0;
    foreach ($paidOrders->get() as $order) {
        $totalTickets += $order->orderItems->sum('quantity');
    }
    
    $stats = [
        'total_orders' => $totalOrders,
        'total_tickets' => $totalTickets,
        'upcoming_events' => $paidOrders->whereHas('event', function($q) {
            $q->where('event_date', '>=', now()->toDateString());
        })->count(),
        'past_events' => $paidOrders->whereHas('event', function($q) {
            $q->where('event_date', '<', now()->toDateString());
        })->count(),
    ];

    // Commandes récentes
    $recentOrders = $user->orders()
        ->visibleInBuyerHistory()
        ->with(['event.category', 'orderItems.ticketType'])
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();

    // Événements à venir
    $upcomingEvents = $user->orders()
        ->where('payment_status', 'paid')
        ->with(['event.category', 'event.ticketTypes'])
        ->whereHas('event', function($q) {
            $q->where('event_date', '>=', now()->toDateString());
        })
        ->orderBy('created_at', 'desc')
        ->limit(3)
        ->get();

    return view('acheteur.dashboard', compact('stats', 'recentOrders', 'upcomingEvents'));
}









}