<?php

namespace App\Http\Controllers\Promoteur;

use App\Http\Controllers\Controller;
use App\Models\Event;

use App\Models\TicketType;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketTypeController extends Controller
{
    /**
     * Liste des types de billets d'un événement
     */
    public function index(Event $event)
    {
        $this->authorize('view', $event);
        
        $ticketTypes = $event->ticketTypes()
            ->withCount(['orderItems', 'tickets'])
            ->withSum(['orderItems as revenue' => function($query) {
                $query->whereHas('order', function($q) {
                    $q->where('status', 'paid');
                });
            }], 'total_price')
            ->orderBy('created_at', 'desc')
            ->get();

        // Enrichir avec des statistiques de scan - CORRIGÉ
        $ticketTypes->map(function($ticketType) {
            $allTickets = $ticketType->tickets;
            $ticketType->used_tickets = $allTickets->where('status', 'used')->count();
            $ticketType->sold_tickets = $allTickets->where('status', 'sold')->count();
            $ticketType->unused_sold = $ticketType->sold_tickets; // Vendus mais pas utilisés
            return $ticketType;
        });

        return view('promoteur.ticket-types.index', compact('event', 'ticketTypes'));
    }

    /**
     * Formulaire de création d'un type de billet
     */
    public function create(Event $event)
    {
        $this->authorize('update', $event);
        
        return view('promoteur.ticket-types.create', compact('event'));
    }

    /**
     * Enregistrer un nouveau type de billet
     */
    public function store(Request $request, Event $event)
    {
        $this->authorize('update', $event);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1|max:10000',
            'sale_start_date' => 'nullable|date',
            'sale_end_date' => 'nullable|date|after:sale_start_date',
            'is_active' => 'boolean'
        ]);

        // Vérifications métier
        if ($request->sale_end_date && Carbon::parse($request->sale_end_date)->gt($event->event_date)) {
            return back()->withErrors([
                'sale_end_date' => 'La date de fin de vente ne peut pas être après la date de l\'événement'
            ]);
        }

        $ticketTypeData = $request->all();
        $ticketTypeData['event_id'] = $event->id;
        $ticketTypeData['is_active'] = $request->has('is_active');

        $ticketType = TicketType::create($ticketTypeData);

        return redirect()->route('promoteur.events.tickets.index', $event)
            ->with('success', 'Type de billet créé avec succès');
    }

    /**
     * Formulaire d'édition d'un type de billet
     */
    public function edit(Event $event, TicketType $ticket)
    {
        $this->authorize('update', $event);
        
        if ($ticket->event_id !== $event->id) {
            abort(404);
        }

        return view('promoteur.ticket-types.edit', compact('event', 'ticket'));
    }

    /**
     * Mettre à jour un type de billet
     */
    public function update(Request $request, Event $event, TicketType $ticket)
    {
        $this->authorize('update', $event);
        
        if ($ticket->event_id !== $event->id) {
            abort(404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1|max:10000',
            'sale_start_date' => 'nullable|date',
            'sale_end_date' => 'nullable|date|after:sale_start_date',
            'is_active' => 'boolean'
        ]);

        // Vérifier qu'on ne réduit pas la quantité en dessous des billets vendus
        $soldTickets = $ticket->orderItems()->whereHas('order', function($query) {
            $query->where('status', 'paid');
        })->sum('quantity');

        if ($request->quantity < $soldTickets) {
            return back()->withErrors([
                'quantity' => "Impossible de réduire la quantité en dessous de {$soldTickets} (billets déjà vendus)"
            ]);
        }

        // Vérifications de dates
        if ($request->sale_end_date && Carbon::parse($request->sale_end_date)->gt($event->event_date)) {
            return back()->withErrors([
                'sale_end_date' => 'La date de fin de vente ne peut pas être après la date de l\'événement'
            ]);
        }

        $ticketTypeData = $request->all();
        $ticketTypeData['is_active'] = $request->has('is_active');

        $ticket->update($ticketTypeData);

        return redirect()->route('promoteur.events.tickets.index', $event)
            ->with('success', 'Type de billet mis à jour avec succès');
    }

    /**
     * Supprimer un type de billet
     */
    public function destroy(Event $event, TicketType $ticket)
    {
        $this->authorize('update', $event);
        
        if ($ticket->event_id !== $event->id) {
            abort(404);
        }

        // Vérifier qu'il n'y a pas de billets vendus
        if ($ticket->orderItems()->whereHas('order', function($query) {
            $query->where('status', 'paid');
        })->exists()) {
            return back()->with('error', 'Impossible de supprimer un type de billet avec des ventes');
        }

        $ticket->delete();

        return redirect()->route('promoteur.events.tickets.index', $event)
            ->with('success', 'Type de billet supprimé avec succès');
    }

    /**
     * Activer/Désactiver un type de billet
     */
    public function toggle(Event $event, TicketType $ticket)
    {
        $this->authorize('update', $event);
        
        if ($ticket->event_id !== $event->id) {
            return response()->json(['success' => false, 'message' => 'Type de billet non trouvé'], 404);
        }

        $ticket->update(['is_active' => !$ticket->is_active]);

        $status = $ticket->is_active ? 'activé' : 'désactivé';
        
        return response()->json([
            'success' => true,
            'message' => "Type de billet {$status}",
            'is_active' => $ticket->is_active
        ]);
    }

    /**
     * Dupliquer un type de billet
     */
    public function duplicate(Event $event, TicketType $ticket)
    {
        $this->authorize('update', $event);
        
        if ($ticket->event_id !== $event->id) {
            abort(404);
        }

        $newTicketType = $ticket->replicate();
        $newTicketType->name = $ticket->name . ' (Copie)';
        $newTicketType->is_active = false;
        $newTicketType->save();

        return response()->json([
            'success' => true,
            'message' => 'Type de billet dupliqué',
            'redirect' => route('promoteur.events.tickets.edit', [$event, $newTicketType])
        ]);
    }

    /**
     * Statistiques d'un type de billet - CORRIGÉ
     */
    public function stats(Event $event, TicketType $ticket)
    {
        $this->authorize('view', $event);
        
        if ($ticket->event_id !== $event->id) {
            abort(404);
        }

        // Statistiques de base
        $soldQuantity = $ticket->orderItems()->whereHas('order', function($query) {
            $query->where('status', 'paid');
        })->sum('quantity');

        $revenue = $ticket->orderItems()->whereHas('order', function($query) {
            $query->where('status', 'paid');
        })->sum('total_price');

        $ordersCount = $ticket->orderItems()->whereHas('order', function($query) {
            $query->where('status', 'paid');
        })->count();

        // Statistiques d'utilisation - CORRIGÉ
        $allTickets = $ticket->tickets;
        $ticketsUsed = $allTickets->where('status', 'used')->count();
        $ticketsUnused = $allTickets->where('status', 'sold')->count();

        $stats = [
            'total_quantity' => $ticket->quantity,
            'sold_quantity' => $soldQuantity,
            'revenue' => $revenue,
            'orders_count' => $ordersCount,
            'tickets_used' => $ticketsUsed,
            'tickets_unused' => $ticketsUnused,
            'remaining_quantity' => $ticket->quantity - $soldQuantity,
            'usage_rate' => $soldQuantity > 0 
                ? round(($ticketsUsed / $soldQuantity) * 100, 1) 
                : 0
        ];

        // Évolution des ventes par jour - CORRIGÉ
        $salesEvolution = $ticket->orderItems()
            ->whereHas('order', function($query) {
                $query->where('status', 'paid');
            })
            ->selectRaw('DATE(created_at) as date, SUM(quantity) as tickets_sold, SUM(total_price) as daily_revenue')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->take(30)
            ->get();

        // Évolution des utilisations par jour
        $usageEvolution = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $usedCount = Ticket::where('ticket_type_id', $ticket->id)
                ->where('status', 'used')
                ->whereDate('updated_at', $date)
                ->count();
                
            $usageEvolution[] = [
                'date' => $date->format('d/m'),
                'count' => $usedCount
            ];
        }

        return response()->json([
            'stats' => $stats,
            'sales_evolution' => $salesEvolution,
            'usage_evolution' => $usageEvolution
        ]);
    }

    /**
     * Vérifier la disponibilité d'un type de billet
     */
    public function checkAvailability(Event $event, TicketType $ticket)
    {
        $this->authorize('view', $event);
        
        if ($ticket->event_id !== $event->id) {
            abort(404);
        }

        $soldQuantity = $ticket->orderItems()->whereHas('order', function($query) {
            $query->where('status', 'paid');
        })->sum('quantity');

        $available = $ticket->quantity - $soldQuantity;
        $isAvailable = $available > 0 && $ticket->is_active;
        
        // Vérifier les dates de vente
        $now = now();
        $saleActive = true;
        
        if ($ticket->sale_start_date && $now->lt($ticket->sale_start_date)) {
            $saleActive = false;
        }
        
        if ($ticket->sale_end_date && $now->gt($ticket->sale_end_date)) {
            $saleActive = false;
        }

        return response()->json([
            'available' => $available,
            'is_available' => $isAvailable && $saleActive,
            'sale_active' => $saleActive,
            'sold_quantity' => $soldQuantity,
            'total_quantity' => $ticket->quantity
        ]);
    }
}