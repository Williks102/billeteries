<?php

namespace App\Http\Controllers\Promoteur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\{Event, EventCategory, Order, TicketType};
use Carbon\Carbon;

class PromoteurController extends Controller
{
    /** Dashboard principal */
public function dashboard()
{
    try {
        $promoteur = Auth::user();

        $stats = [
            'total_events' => $promoteur->events()->count() ?? 0,
            'published_events' => $promoteur->events()->where('status', 'published')->count() ?? 0,
            'upcoming_events' => $promoteur->events()
                ->where('status', 'published')
                ->where('event_date', '>=', now()->toDateString())
                ->count() ?? 0,
            'total_revenue' => $promoteur->totalRevenue() ?? 0,
            'pending_revenue' => $promoteur->pendingRevenue() ?? 0,
        ];

        $recentEvents = $promoteur->events()
            ->with(['category', 'ticketTypes'])
            ->latest()
            ->limit(5)
            ->get();

        $recentOrders = Order::whereHas('event', function($q) use ($promoteur) {
                $q->where('promoteur_id', $promoteur->id);
            })
            ->with(['user', 'event'])
            ->where('payment_status', 'paid')
            ->latest()
            ->limit(10)
            ->get();

        return view('promoteur.dashboard', compact('stats', 'recentEvents', 'recentOrders'));
        
    } catch (\Exception $e) {
        \Log::error('Erreur dans promoteur.dashboard: ' . $e->getMessage());
        
        // Données par défaut en cas d'erreur
        $stats = [
            'total_events' => 0,
            'published_events' => 0,
            'upcoming_events' => 0,
            'total_revenue' => 0,
            'pending_revenue' => 0,
        ];
        
        $recentEvents = collect();
        $recentOrders = collect();
        
        return view('promoteur.dashboard', compact('stats', 'recentEvents', 'recentOrders'))
            ->with('error', 'Erreur lors du chargement du dashboard');
    }
}

    /** Liste des événements */
/** Liste des événements - VERSION CORRIGÉE */
public function events(Request $request)
{
    try {
        $query = Auth::user()->events()->with(['category', 'ticketTypes']);
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        $events = $query->latest()->paginate(12);
        $categories = EventCategory::all();

        return view('promoteur.events.index', compact('events', 'categories'));
        
    } catch (\Exception $e) {
        \Log::error('Erreur dans promoteur.events: ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());
        
        // Retour avec des données vides en cas d'erreur
        $events = collect();
        $categories = collect();
        
        return view('promoteur.events.index', compact('events', 'categories'))
            ->with('error', 'Erreur lors du chargement des événements');
    }
}

    /** Formulaire création */
    public function create()
    {
        $categories = EventCategory::all();
        return view('promoteur.events.create', compact('categories'));
    }

    /** Sauvegarde événement */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required', 'description' => 'required',
            'category_id' => 'required|exists:event_categories,id',
            'venue' => 'required', 'address' => 'required',
            'event_date' => 'required|date|after:today',
            'event_time' => 'required|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:event_time',
            'image' => 'nullable|image|max:2048',
        ]);

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('events', 'public')
            : null;

        $event = Event::create([
            'promoteur_id' => Auth::id(),
            'category_id' => $request->category_id,
            'title' => $request->title,
            'description' => $request->description,
            'venue' => $request->venue,
            'address' => $request->address,
            'event_date' => $request->event_date,
            'event_time' => Carbon::createFromFormat('Y-m-d H:i', $request->event_date . ' ' . $request->event_time),
            'end_time' => $request->end_time ? Carbon::createFromFormat('Y-m-d H:i', $request->event_date . ' ' . $request->end_time) : null,
            'status' => 'draft',
            'image' => $imagePath,
        ]);

        return redirect()->route('promoteur.events.tickets.create', $event)->with('success', 'Événement créé. Configurez les billets.');
    }

public function show(Event $event)
{
    try {
        abort_if($event->promoteur_id !== Auth::id(), 403);

        $event->load(['category', 'ticketTypes', 'orders.user']);
        
        $stats = [
            'total_revenue' => $event->totalRevenue(),
            'tickets_sold' => $event->getTicketsSoldCount(), // Utiliser la méthode alternative
            'tickets_available' => $event->totalTicketsAvailable(),
            'orders_count' => $event->getOrdersCount(), // Utiliser la méthode alternative
            'progress_percentage' => $event->getProgressPercentage(),
        ];

        $recentOrders = $event->orders()
            ->where('payment_status', 'paid')
            ->with('user')
            ->latest()
            ->limit(10)
            ->get();
            
        return view('promoteur.events.show', compact('event', 'stats', 'recentOrders'));
        
    } catch (\Exception $e) {
        \Log::error('Erreur dans promoteur.events.show: ' . $e->getMessage());
        
        return redirect()->route('promoteur.events.index')
            ->with('error', 'Erreur lors du chargement de l\'événement');
    }
}



    public function publish(Event $event)
    {
        abort_if($event->promoteur_id !== Auth::id(), 403);

        if ($event->ticketTypes()->where('is_active', true)->count() === 0) {
            return back()->with('error', 'Ajoutez au moins un billet avant publication.');
        }

        $event->update(['status' => 'published']);
        return back()->with('success', 'Événement publié.');
    }

    public function unpublish(Event $event)
    {
        abort_if($event->promoteur_id !== Auth::id(), 403);
        $event->update(['status' => 'draft']);
        return back()->with('success', 'Événement dépublié.');
    }

    public function scanner() { return view('promoteur.scanner'); }

    public function verifyTicket(Request $request)
{
    $request->validate(['ticket_code' => 'required|string']);
    
    try {
        $ticket = \App\Models\Ticket::where('ticket_code', $request->ticket_code)
            ->with('ticketType.event')
            ->first();

        if (!$ticket) {
            return response()->json([
                'success' => false, 
                'message' => 'Billet non trouvé'
            ], 404);
        }

        // Vérifier que le promoteur peut scanner ce billet
        if (!$ticket->ticketType || !$ticket->ticketType->event) {
            return response()->json([
                'success' => false, 
                'message' => 'Événement associé non trouvé'
            ], 404);
        }

        if ($ticket->ticketType->event->promoteur_id !== Auth::id()) {
            return response()->json([
                'success' => false, 
                'message' => 'Vous n\'êtes pas autorisé à scanner ce billet'
            ], 403);
        }

        if ($ticket->status === 'sold') {
            $ticket->markAsUsed();
            return response()->json([
                'success' => true, 
                'message' => 'Billet validé avec succès', 
                'ticket' => $ticket->getFullTicketInfo()
            ]);
        } else {
            return response()->json([
                'success' => false, 
                'message' => 'Billet déjà utilisé ou invalide (statut: ' . $ticket->status . ')', 
                'ticket' => $ticket->getFullTicketInfo()
            ]);
        }
    } catch (\Exception $e) {
        \Log::error('Erreur lors de la vérification du billet: ' . $e->getMessage());
        return response()->json([
            'success' => false, 
            'message' => 'Erreur lors de la vérification du billet'
        ], 500);
    }
}

/**
 * Afficher le formulaire d'édition d'un événement
 */
public function edit(Event $event)
{
    // Vérifier que l'événement appartient au promoteur connecté
    abort_if($event->promoteur_id !== Auth::id(), 403, 'Vous n\'êtes pas autorisé à modifier cet événement');
    
    try {
        $categories = EventCategory::all();
        return view('promoteur.events.edit', compact('event', 'categories'));
        
    } catch (\Exception $e) {
        \Log::error('Erreur lors de l\'affichage du formulaire d\'édition: ' . $e->getMessage());
        
        return redirect()->route('promoteur.events.index')
            ->with('error', 'Impossible de charger le formulaire d\'édition');
    }
}

/**
 * Mettre à jour un événement existant
 */
public function update(Request $request, Event $event)
{
    // Vérifier que l'événement appartient au promoteur connecté
    abort_if($event->promoteur_id !== Auth::id(), 403, 'Vous n\'êtes pas autorisé à modifier cet événement');

    $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'required|string',
        'category_id' => 'required|exists:event_categories,id',
        'venue' => 'required|string|max:255',
        'address' => 'required|string',
        'event_date' => 'required|date',
        'event_time' => 'required|date_format:H:i',
        'end_time' => 'nullable|date_format:H:i|after:event_time',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);

    try {
        // Gestion de l'image
        $imagePath = $event->image; // Garder l'ancienne image par défaut
        
        if ($request->hasFile('image')) {
            // Supprimer l'ancienne image si elle existe
            if ($event->image && Storage::disk('public')->exists($event->image)) {
                Storage::disk('public')->delete($event->image);
            }
            
            // Sauvegarder la nouvelle image
            $imagePath = $request->file('image')->store('events', 'public');
        }

        // Mettre à jour l'événement
        $event->update([
            'category_id' => $request->category_id,
            'title' => $request->title,
            'description' => $request->description,
            'venue' => $request->venue,
            'address' => $request->address,
            'event_date' => $request->event_date,
            'event_time' => Carbon::createFromFormat('Y-m-d H:i', $request->event_date . ' ' . $request->event_time),
            'end_time' => $request->end_time ? Carbon::createFromFormat('Y-m-d H:i', $request->event_date . ' ' . $request->end_time) : null,
            'image' => $imagePath,
        ]);

        return redirect()->route('promoteur.events.show', $event)
            ->with('success', 'Événement mis à jour avec succès !');
            
    } catch (\Exception $e) {
        \Log::error('Erreur lors de la mise à jour de l\'événement: ' . $e->getMessage());
        
        return redirect()->back()
            ->with('error', 'Erreur lors de la mise à jour de l\'événement')
            ->withInput();
    }
}

/**
 * Supprimer un événement
 */
public function destroy(Event $event)
{
    // Vérifier que l'événement appartient au promoteur connecté
    abort_if($event->promoteur_id !== Auth::id(), 403, 'Vous n\'êtes pas autorisé à supprimer cet événement');

    try {
        // Vérifier qu'il n'y a pas de commandes payées pour cet événement
        $paidOrders = $event->orders()->where('payment_status', 'paid')->count();
        
        if ($paidOrders > 0) {
            return redirect()->back()
                ->with('error', 'Impossible de supprimer un événement avec des billets vendus');
        }

        // Supprimer l'image si elle existe
        if ($event->image && Storage::disk('public')->exists($event->image)) {
            Storage::disk('public')->delete($event->image);
        }

        // Supprimer l'événement
        $event->delete();

        return redirect()->route('promoteur.events.index')
            ->with('success', 'Événement supprimé avec succès');
            
    } catch (\Exception $e) {
        \Log::error('Erreur lors de la suppression de l\'événement: ' . $e->getMessage());
        
        return redirect()->back()
            ->with('error', 'Erreur lors de la suppression de l\'événement');
    }
}

/**
 * Dupliquer un événement
 */
public function duplicate(Event $event)
{
    // Vérifier que l'événement appartient au promoteur connecté
    abort_if($event->promoteur_id !== Auth::id(), 403);

    try {
        // Créer une copie de l'événement
        $newEvent = $event->replicate();
        $newEvent->title = $event->title . ' (Copie)';
        $newEvent->status = 'draft';
        $newEvent->event_date = now()->addDays(7); // 7 jours dans le futur
        $newEvent->created_at = now();
        $newEvent->updated_at = now();
        $newEvent->save();

        // Dupliquer les types de billets
        foreach ($event->ticketTypes as $ticketType) {
            $newTicketType = $ticketType->replicate();
            $newTicketType->event_id = $newEvent->id;
            $newTicketType->quantity_sold = 0; // Reset des billets vendus
            $newTicketType->save();
        }

        return redirect()->route('promoteur.events.edit', $newEvent)
            ->with('success', 'Événement dupliqué avec succès ! Vous pouvez maintenant le modifier.');
            
    } catch (\Exception $e) {
        \Log::error('Erreur lors de la duplication de l\'événement: ' . $e->getMessage());
        
        return redirect()->back()
            ->with('error', 'Erreur lors de la duplication de l\'événement');
    }
}

/**
 * Page des ventes - VERSION CORRIGÉE
 */
public function sales(Request $request)
{
    $promoteur = Auth::user();
    
    // Période par défaut : ce mois
    $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
    $endDate = $request->get('end_date', now()->endOfMonth()->toDateString());
    
    try {
        // Ventes par événement avec gestion d'erreur
        $salesByEvent = $promoteur->events()
            ->with(['orders' => function($query) use ($startDate, $endDate) {
                $query->where('payment_status', 'paid')
                      ->whereBetween('created_at', [$startDate, $endDate]);
            }])
            ->get()
            ->map(function($event) {
                return [
                    'event' => $event,
                    'revenue' => $event->orders ? $event->orders->sum('total_amount') : 0,
                    'tickets_sold' => $event->orders ? $event->orders->sum(function($order) {
                        return $order->orderItems ? $order->orderItems->sum('quantity') : 0;
                    }) : 0,
                    'orders_count' => $event->orders ? $event->orders->count() : 0,
                ];
            })
            ->sortByDesc('revenue');
    } catch (\Exception $e) {
        \Log::error('Erreur lors de la récupération des ventes par événement: ' . $e->getMessage());
        $salesByEvent = collect();
    }
    
    try {
        // Ventes par jour avec gestion d'erreur
        $dailySales = Order::whereHas('event', function($query) use ($promoteur) {
                $query->where('promoteur_id', $promoteur->id);
            })
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, SUM(total_amount) as revenue, COUNT(*) as orders')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    } catch (\Exception $e) {
        \Log::error('Erreur lors de la récupération des ventes quotidiennes: ' . $e->getMessage());
        $dailySales = collect();
    }
    
    // Statistiques globales avec protection
    $totalStats = [
        'total_revenue' => $salesByEvent->sum('revenue') ?? 0,
        'total_tickets' => $salesByEvent->sum('tickets_sold') ?? 0,
        'total_orders' => $salesByEvent->sum('orders_count') ?? 0,
        'average_order' => 0,
    ];
    
    // Calcul du panier moyen avec protection division par zéro
    if ($totalStats['total_orders'] > 0) {
        $totalStats['average_order'] = $totalStats['total_revenue'] / $totalStats['total_orders'];
    }
    
    return view('promoteur.sales', compact(
        'salesByEvent', 'dailySales', 'totalStats', 'startDate', 'endDate'
    ));
}
/**
 * Page des commissions - VERSION CORRIGÉE
 */
public function commissions(Request $request)
{
    $promoteur = Auth::user();
    
    $query = $promoteur->commissions()
        ->with(['order.event', 'order.user'])
        ->orderBy('created_at', 'desc');
    
    // Filtres
    if ($request->has('status') && $request->status !== '') {
        $query->where('status', $request->status);
    }
    
    if ($request->has('month') && $request->month !== '') {
        $query->whereMonth('created_at', $request->month);
    }
    
    if ($request->has('year') && $request->year !== '') {
        $query->whereYear('created_at', $request->year);
    }
    
    try {
        $commissions = $query->paginate(20);
    } catch (\Exception $e) {
        \Log::error('Erreur lors de la récupération des commissions: ' . $e->getMessage());
        $commissions = collect(); // Collection vide en cas d'erreur
    }
    
    // Totaux avec gestion d'erreur
    try {
        $totals = [
            'paid' => $promoteur->commissions()->where('status', 'paid')->sum('net_amount') ?? 0,
            'pending' => $promoteur->commissions()->where('status', 'pending')->sum('net_amount') ?? 0,
            'held' => $promoteur->commissions()->where('status', 'held')->sum('net_amount') ?? 0,
            'total' => $promoteur->commissions()->sum('net_amount') ?? 0,
        ];
    } catch (\Exception $e) {
        \Log::error('Erreur lors du calcul des totaux: ' . $e->getMessage());
        $totals = ['paid' => 0, 'pending' => 0, 'held' => 0, 'total' => 0];
    }
    
    // Commissions prêtes à être payées
    try {
        $readyForPayout = $promoteur->commissions()
            ->where('status', 'pending')
            ->whereHas('order', function($query) {
                $query->where('payment_status', 'paid');
            })
            ->where('created_at', '<=', now()->subDays(7))
            ->sum('net_amount') ?? 0;
    } catch (\Exception $e) {
        \Log::error('Erreur lors du calcul des commissions prêtes: ' . $e->getMessage());
        $readyForPayout = 0;
    }
    
    return view('promoteur.commissions', compact('commissions', 'totals', 'readyForPayout'));
}


/**
 * Page des rapports - VERSION CORRIGÉE
 */
public function reports(Request $request)
{
    $promoteur = Auth::user();
    
    // Période par défaut : ce mois
    $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
    $endDate = $request->get('end_date', now()->endOfMonth()->toDateString());
    
    try {
        // Statistiques générales avec gestion d'erreur
        $stats = [
            'total_events' => $promoteur->events()->count() ?? 0,
            'published_events' => $promoteur->events()->where('status', 'published')->count() ?? 0,
            'draft_events' => $promoteur->events()->where('status', 'draft')->count() ?? 0,
            'total_revenue' => $promoteur->totalRevenue() ?? 0,
            'period_revenue' => $promoteur->commissions()
                ->where('status', 'paid')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('net_amount') ?? 0,
        ];
    } catch (\Exception $e) {
        \Log::error('Erreur lors du calcul des statistiques: ' . $e->getMessage());
        $stats = [
            'total_events' => 0,
            'published_events' => 0, 
            'draft_events' => 0,
            'total_revenue' => 0,
            'period_revenue' => 0
        ];
    }
    
    try {
        // Événements les plus performants avec gestion d'erreur
        $topEvents = $promoteur->events()
            ->withSum(['orders as revenue' => function($query) {
                $query->where('payment_status', 'paid');
            }], 'total_amount')
            ->withCount(['orders as orders_count' => function($query) {
                $query->where('payment_status', 'paid');
            }])
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();
    } catch (\Exception $e) {
        \Log::error('Erreur lors de la récupération des top événements: ' . $e->getMessage());
        $topEvents = collect();
    }
    
    try {
        // Évolution mensuelle (12 derniers mois) avec gestion d'erreur
        $monthlyData = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            
            $revenue = $promoteur->commissions()
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->where('status', 'paid')
                ->sum('net_amount') ?? 0;
            
            $monthlyData[] = [
                'month' => $date->format('M Y'),
                'revenue' => $revenue
            ];
        }
    } catch (\Exception $e) {
        \Log::error('Erreur lors du calcul de l\'évolution mensuelle: ' . $e->getMessage());
        $monthlyData = [];
    }
    
    try {
        // Types de billets les plus vendus avec gestion d'erreur
        $topTicketTypes = \App\Models\TicketType::whereHas('event', function($query) use ($promoteur) {
                $query->where('promoteur_id', $promoteur->id);
            })
            ->withSum(['orderItems as revenue' => function($query) use ($startDate, $endDate) {
                $query->whereHas('order', function($q) use ($startDate, $endDate) {
                    $q->where('payment_status', 'paid')
                      ->whereBetween('created_at', [$startDate, $endDate]);
                });
            }], 'total_price')
            ->withSum(['orderItems as tickets_sold' => function($query) use ($startDate, $endDate) {
                $query->whereHas('order', function($q) use ($startDate, $endDate) {
                    $q->where('payment_status', 'paid')
                      ->whereBetween('created_at', [$startDate, $endDate]);
                });
            }], 'quantity')
            ->having('tickets_sold', '>', 0)
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();
    } catch (\Exception $e) {
        \Log::error('Erreur lors de la récupération des types de billets: ' . $e->getMessage());
        $topTicketTypes = collect();
    }
    
    return view('promoteur.reports', compact(
        'stats', 'topEvents', 'monthlyData', 'topTicketTypes', 
        'startDate', 'endDate'
    ));
}

/**
 * Export des données avec gestion d'erreur - VERSION CORRIGÉE
 */
public function exportData(Request $request)
{
    $type = $request->get('type', 'commissions');
    $format = $request->get('format', 'excel');
    
    try {
        switch ($type) {
            case 'commissions':
                return $this->exportCommissions($request);
            case 'sales':
                return $this->exportSales($request);
            case 'events':
                return $this->exportEvents($request);
            default:
                return redirect()->back()->with('error', 'Type d\'export non valide');
        }
    } catch (\Exception $e) {
        \Log::error('Erreur lors de l\'export ' . $type . ': ' . $e->getMessage());
        return redirect()->back()->with('error', 'Erreur lors de l\'export: ' . $e->getMessage());
    }
}

/**
 * Export des commissions
 */
private function exportCommissions(Request $request)
{
    $promoteur = Auth::user();
    
    $commissions = $promoteur->commissions()
        ->with(['order.event', 'order.user'])
        ->orderBy('created_at', 'desc')
        ->get();
    
    $filename = 'commissions_' . $promoteur->name . '_' . now()->format('Y-m-d') . '.csv';
    
    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
    ];
    
    $callback = function() use ($commissions) {
        $file = fopen('php://output', 'w');
        
        // En-têtes CSV
        fputcsv($file, [
            'Date',
            'Événement',
            'Client',
            'Montant Brut (FCFA)',
            'Commission (%)',
            'Net (FCFA)',
            'Statut',
            'Date Paiement'
        ]);
        
        // Données
        foreach ($commissions as $commission) {
            fputcsv($file, [
                $commission->created_at->format('d/m/Y'),
                $commission->order->event->title,
                $commission->order->user->name,
                number_format($commission->gross_amount),
                $commission->commission_rate . '%',
                number_format($commission->net_amount),
                ucfirst($commission->status),
                $commission->paid_at ? $commission->paid_at->format('d/m/Y') : ''
            ]);
        }
        
        fclose($file);
    };
    
    return response()->stream($callback, 200, $headers);
}

/**
 * Export des ventes
 */
private function exportSales(Request $request)
{
    $promoteur = Auth::user();
    
    $orders = Order::whereHas('event', function($query) use ($promoteur) {
            $query->where('promoteur_id', $promoteur->id);
        })
        ->with(['user', 'event', 'orderItems.ticketType'])
        ->where('payment_status', 'paid')
        ->orderBy('created_at', 'desc')
        ->get();
    
    $filename = 'ventes_' . $promoteur->name . '_' . now()->format('Y-m-d') . '.csv';
    
    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
    ];
    
    $callback = function() use ($orders) {
        $file = fopen('php://output', 'w');
        
        // En-têtes CSV
        fputcsv($file, [
            'Date',
            'Commande',
            'Client',
            'Email',
            'Événement',
            'Billets',
            'Montant (FCFA)'
        ]);
        
        // Données
        foreach ($orders as $order) {
            fputcsv($file, [
                $order->created_at->format('d/m/Y H:i'),
                $order->order_number,
                $order->user->name,
                $order->billing_email,
                $order->event->title,
                $order->orderItems->sum('quantity'),
                number_format($order->total_amount)
            ]);
        }
        
        fclose($file);
    };
    
    return response()->stream($callback, 200, $headers);
}

/**
 * Export des événements
 */
private function exportEvents(Request $request)
{
    $promoteur = Auth::user();
    
    $events = $promoteur->events()
        ->with(['category', 'ticketTypes'])
        ->withSum(['orders as revenue' => function($query) {
            $query->where('payment_status', 'paid');
        }], 'total_amount')
        ->withCount(['orders as orders_count' => function($query) {
            $query->where('payment_status', 'paid');
        }])
        ->orderBy('created_at', 'desc')
        ->get();
    
    $filename = 'evenements_' . $promoteur->name . '_' . now()->format('Y-m-d') . '.csv';
    
    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
    ];
    
    $callback = function() use ($events) {
        $file = fopen('php://output', 'w');
        
        // En-têtes CSV
        fputcsv($file, [
            'Titre',
            'Catégorie',
            'Date',
            'Lieu',
            'Statut',
            'Billets Disponibles',
            'Billets Vendus',
            'Revenus (FCFA)',
            'Commandes'
        ]);
        
        // Données
        foreach ($events as $event) {
            fputcsv($file, [
                $event->title,
                $event->category->name,
                $event->event_date ? $event->event_date->format('d/m/Y') : '',
                $event->venue,
                ucfirst($event->status),
                $event->totalTicketsAvailable(),
                $event->totalTicketsSold(),
                number_format($event->revenue ?? 0),
                $event->orders_count ?? 0
            ]);
        }
        
        fclose($file);
    };
    
    return response()->stream($callback, 200, $headers);
}
}
