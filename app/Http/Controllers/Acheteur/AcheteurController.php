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
    $totalOrders = $user->orders()->count();
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

    /**
     * Mes billets
     */
    public function myTickets(Request $request)
    {
        $user = Auth::user();
        
        $query = $user->orders()
            ->where('payment_status', 'paid')
            ->with(['event.category', 'tickets.ticketType', 'orderItems.ticketType']);

        // Filtrer par statut
        if ($request->has('status') && $request->status != '') {
            if ($request->status == 'upcoming') {
                $query->whereHas('event', function($q) {
                    $q->where('event_date', '>=', now()->toDateString());
                });
            } elseif ($request->status == 'past') {
                $query->whereHas('event', function($q) {
                    $q->where('event_date', '<', now()->toDateString());
                });
            }
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('acheteur.tickets', compact('orders'));
    }

    /**
     * Détail d'une commande
     */
    public function orderDetail(Order $order)
    {
        // Vérifier que l'utilisateur peut voir cette commande
        if ($order->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403);
        }

        $order->load([
            'event.category', 
            'orderItems.ticketType', 
            'tickets.ticketType',
            'commission'
        ]);

        return view('acheteur.order-detail', compact('order'));
    }

    public function downloadTickets(Order $order)
{
    // Vérifications existantes...
    
    // Charger les relations nécessaires
    $order->load(['event.category', 'tickets.ticketType', 'orderItems.ticketType', 'user']);
    
    // Générer le PDF avec DomPDF
    $pdf = \PDF::loadView('acheteur.tickets-pdf', compact('order'));
    
    // Configuration du PDF
    $pdf->setPaper('A4', 'portrait');
    $pdf->setOptions(['dpi' => 150, 'defaultFont' => 'sans-serif']);
    
    // Nom du fichier
    $fileName = 'billets-' . $order->order_number . '-' . $order->event->title . '.pdf';
    $fileName = \Str::slug($fileName) . '.pdf';
    
    // Télécharger le PDF
    return $pdf->download($fileName);
}

    /**
     * Afficher un billet individuel avec QR code
     */
    public function showTicket(Ticket $ticket)
    {
        // Vérifier que l'utilisateur peut voir ce billet
        $order = $ticket->order()->where('user_id', Auth::id())->first();
        if (!$order && !Auth::user()->isAdmin()) {
            abort(403);
        }

        $ticket->load(['ticketType.event.category']);

        return view('acheteur.ticket-detail', compact('ticket'));
    }

    /**
     * Profil utilisateur
     */
    public function profile()
    {
        $user = Auth::user();
        return view('acheteur.profile', compact('user'));
    }

    /**
     * Mettre à jour le profil
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . Auth::id(),
            'phone' => 'nullable|string|max:20',
        ]);

        $user = Auth::user();
        $user->update($request->only(['name', 'email', 'phone']));

        return redirect()->back()->with('success', 'Profil mis à jour avec succès.');
    }
}