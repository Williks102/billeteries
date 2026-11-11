<?php

namespace App\Http\Controllers\Acheteur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Ticket;

/**
 * Billets de l'acheteur (ressource : index, show).
 */
class TicketController extends Controller
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
     * Mes billets
     */
    public function index(Request $request)
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

        return view('acheteur.tickets.index', compact('orders'));
    }

    /**
     * Afficher un billet individuel avec QR code
     */
    public function show(Ticket $ticket)
    {
        // Vérifier que l'utilisateur peut voir ce billet
        $order = $ticket->order()->where('user_id', Auth::id())->first();
        if (!$order && !Auth::user()->isAdmin()) {
            abort(403);
        }

        $ticket->load(['ticketType.event.category']);

        return view('acheteur.ticket-detail', compact('ticket'));
    }
}
