<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\Commission;
use App\Models\EventCategory;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    /**
     * Dashboard principal - CONSERVÉ
     */
    public function dashboard()
    {
        try {
            // Statistiques générales
            $stats = [
                'total_users' => User::count(),
                'total_events' => Event::count(),
                'total_orders' => Order::count(),
                'total_revenue' => Order::where('payment_status', 'paid')->sum('total_amount'),
                'pending_events' => Event::where('status', 'pending')->count(),
                'this_month_revenue' => Order::where('payment_status', 'paid')
                    ->whereMonth('created_at', now()->month)
                    ->sum('total_amount'),
                'verified_users' => User::whereNotNull('email_verified_at')->count(),
                'promoteurs_actifs' => User::where('role', 'promoteur')
                    ->whereHas('events', function($q) {
                        $q->where('status', 'published');
                    })->count(),
            ];

            // Évolution mensuelle des revenus (6 derniers mois)
            $monthlyStats = collect();
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $monthlyStats->push([
                    'month' => $date->format('M Y'),
                    'revenue' => Order::where('payment_status', 'paid')
                        ->whereYear('created_at', $date->year)
                        ->whereMonth('created_at', $date->month)
                        ->sum('total_amount'),
                    'orders' => Order::whereYear('created_at', $date->year)
                        ->whereMonth('created_at', $date->month)
                        ->count(),
                    'events' => Event::whereYear('created_at', $date->year)
                        ->whereMonth('created_at', $date->month)
                        ->count(),
                ]);
            }

            // Événements nécessitant attention
            $pendingEvents = Event::where('status', 'pending')
                ->with(['promoteur', 'category'])
                ->latest()
                ->take(5)
                ->get();

            // Commandes récentes
            $recentOrders = Order::with(['user', 'event'])
                ->latest()
                ->take(10)
                ->get();

            // Top promoteurs du mois
            $topPromoters = User::where('role', 'promoteur')
                ->withCount(['events as events_this_month' => function($query) {
                    $query->whereMonth('created_at', now()->month);
                }])
                ->withSum(['commissions as revenue_this_month' => function($query) {
                    $query->whereMonth('created_at', now()->month)
                          ->where('status', 'paid');
                }], 'net_amount')
                ->orderByDesc('revenue_this_month')
                ->take(5)
                ->get();

            return view('admin.dashboard', compact(
                'stats', 
                'monthlyStats', 
                'pendingEvents', 
                'recentOrders',
                'topPromoters'
            ));

        } catch (\Exception $e) {
            \Log::error('Erreur dashboard admin: ' . $e->getMessage());
            
            return view('admin.dashboard', [
                'stats' => $this->getDefaultStats(),
                'monthlyStats' => collect(),
                'pendingEvents' => collect(),
                'recentOrders' => collect(),
                'topPromoters' => collect(),
            ]);
        }
    }

    // ================================================================
    // GESTION DES ÉVÉNEMENTS - CONSERVÉ TEMPORAIREMENT
    // ================================================================

    /**
     * Liste des événements
     */
    public function events(Request $request)
    {
        $query = Event::with(['category', 'promoteur', 'ticketTypes']);
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }
        
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }
        
        $events = $query->latest()->paginate(20);
        $categories = EventCategory::all();
        
        return view('admin.events', compact('events', 'categories'));
    }

    /**
     * Affichage événement
     */
    public function showEvent(Event $event)
    {
        try {
            $event->load(['category', 'promoteur', 'ticketTypes', 'orders.user', 'tickets']);
            
            // Statistiques de l'événement
            $stats = [
                'total_tickets' => $event->ticketTypes->sum('quantity_available') ?? 0,
                'sold_tickets' => $event->ticketTypes->sum('quantity_sold') ?? 0,
                'total_revenue' => $event->totalRevenue() ?? 0,
                'total_orders' => $event->orders()->count(),
                'pending_orders' => $event->orders()->where('payment_status', 'pending')->count(),
                'used_tickets' => $event->tickets()->where('status', 'used')->count(),
            ];

            // Commandes récentes pour cet événement
            $recentOrders = $event->orders()
                ->with(['user', 'orderItems.ticketType'])
                ->latest()
                ->take(10)
                ->get();

            return view('admin.events.show', compact('event', 'stats', 'recentOrders'));

        } catch (\Exception $e) {
            \Log::error('Erreur affichage événement: ' . $e->getMessage());
            return redirect()->route('admin.events.index')->with('error', 'Erreur lors du chargement');
        }
    }

    /**
     * Édition événement
     */
    public function editEvent(Event $event)
    {
        try {
            $event->load(['category', 'promoteur', 'ticketTypes']);
            $categories = EventCategory::orderBy('name')->get() ?? collect();
            $promoteurs = User::where('role', 'promoteur')->orderBy('name')->get() ?? collect();

            return view('admin.events.edit', compact('event', 'categories', 'promoteurs'));

        } catch (\Exception $e) {
            \Log::error('Erreur édition événement: ' . $e->getMessage());
            return redirect()->route('admin.events.index')->with('error', 'Impossible de charger l\'édition');
        }
    }

    /**
     * Mise à jour événement
     */
    public function updateEvent(Request $request, Event $event)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:event_categories,id',
            'promoteur_id' => 'required|exists:users,id',
            'event_date' => 'required|date',
            'event_time' => 'required',
            'venue' => 'required|string|max:255',
            'address' => 'nullable|string',
            'status' => 'required|in:draft,pending,published,rejected',
        ]);

        try {
            $data = $request->only([
                'title', 'description', 'category_id', 'promoteur_id', 
                'event_date', 'event_time', 'venue', 'address', 'status'
            ]);

            $event->update($data);

            return redirect()->route('admin.events.index')
                ->with('success', 'Événement mis à jour avec succès');

        } catch (\Exception $e) {
            \Log::error('Erreur mise à jour événement: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de la mise à jour')->withInput();
        }
    }

    /**
     * Mise à jour du statut d'un événement
     */
    public function updateEventStatus(Request $request, Event $event)
    {
        $request->validate([
            'status' => 'required|in:draft,pending,published,rejected',
        ]);

        try {
            $event->update(['status' => $request->status]);
            
            return response()->json([
                'success' => true,
                'message' => 'Statut mis à jour avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour'
            ], 500);
        }
    }

    // ================================================================
    // GESTION DES COMMANDES - CONSERVÉ TEMPORAIREMENT
    // ================================================================

    /**
     * Liste des commandes
     */
    public function orders(Request $request)
    {
        $orders = Order::with(['user', 'event'])
            ->when($request->search, function($query, $search) {
                $query->where('order_number', 'like', "%{$search}%")
                      ->orWhereHas('user', function($q) use ($search) {
                          $q->where('name', 'like', "%{$search}%");
                      });
            })
            ->when($request->payment_status, function($query, $status) {
                $query->where('payment_status', $status);
            })
            ->latest()
            ->paginate(15);

        // Statistiques
        $stats = [
            'paid' => Order::where('payment_status', 'paid')->count(),
            'pending' => Order::where('payment_status', 'pending')->count(),
            'failed' => Order::where('payment_status', 'failed')->count(),
            'refunded' => Order::where('payment_status', 'refunded')->count(),
            'total_revenue' => Order::where('payment_status', 'paid')->sum('total_amount')
        ];

        return view('admin.orders', compact('orders', 'stats'));
    }

    /**
     * Détail d'une commande
     */
    public function orderDetail(Order $order)
    {
        $order->load(['user', 'event', 'orderItems.ticketType', 'tickets']);
        return view('admin.order-detail', compact('order'));
    }

    // ================================================================
    // GESTION DES TICKETS - CONSERVÉ TEMPORAIREMENT
    // ================================================================

    /**
     * Liste des tickets
     */
    public function tickets(Request $request)
    {
        $query = Ticket::with(['ticketType.event.promoteur']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('ticket_code', 'like', "%{$search}%")
                  ->orWhereHas('ticketType.event', function($eq) use ($search) {
                      $eq->where('title', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(20);

        $stats = [
            'total' => Ticket::count(),
            'sold' => Ticket::where('status', 'sold')->count(),
            'used' => Ticket::where('status', 'used')->count(),
            'cancelled' => Ticket::where('status', 'cancelled')->count(),
        ];

        return view('admin.tickets', compact('tickets', 'stats'));
    }

    /**
     * Affichage d'un ticket
     */
    public function showTicket(Ticket $ticket)
    {
        $ticket->load(['ticketType.event.promoteur', 'orderItem.order.user']);
        return view('admin.ticket-detail', compact('ticket'));
    }

    /**
     * Marquer ticket comme utilisé
     */
    public function markTicketUsed(Ticket $ticket)
    {
        try {
            $ticket->update(['status' => 'used']);
            
            return response()->json([
                'success' => true,
                'message' => 'Ticket marqué comme utilisé'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour'
            ], 500);
        }
    }

    /**
     * Annuler un ticket
     */
    public function cancelTicket(Ticket $ticket)
    {
        try {
            $ticket->update(['status' => 'cancelled']);
            
            return response()->json([
                'success' => true,
                'message' => 'Ticket annulé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation'
            ], 500);
        }
    }

    /**
     * Réactiver un ticket
     */
    public function reactivateTicket(Ticket $ticket)
    {
        try {
            $ticket->update(['status' => 'sold']);
            
            return response()->json([
                'success' => true,
                'message' => 'Ticket réactivé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réactivation'
            ], 500);
        }
    }

    // ================================================================
    // COMMISSIONS ET FINANCES - CONSERVÉ
    // ================================================================

    /**
     * Gestion des commissions
     */
    public function commissions(Request $request)
    {
        $query = Commission::with(['promoteur', 'order.event']);
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('promoter')) {
            $query->where('promoteur_id', $request->promoter);
        }
        
        $commissions = $query->latest()->paginate(20);
        $promoteurs = User::where('role', 'promoteur')->orderBy('name')->get();
        
        return view('admin.commissions', compact('commissions', 'promoteurs'));
    }

    /**
     * Revenus et analytics
     */
    public function revenues(Request $request)
    {
        $period = $request->get('period', 'this_month');
        $dateRange = $this->getDateRange($period);
        
        $revenue = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', $dateRange)
            ->sum('total_amount');
            
        $orders = Order::whereBetween('created_at', $dateRange)->count();
        
        return view('admin.revenues', compact('revenue', 'orders', 'period'));
    }

    // ================================================================
    // PARAMÈTRES ET PROFIL - CONSERVÉ
    // ================================================================

    /**
     * Paramètres système
     */
    public function settings()
    {
        return view('admin.settings');
    }

    /**
     * Profil admin
     */
    public function profile()
    {
        try {
            $user = auth()->user();
            
            $activityStats = [
                'users_managed' => User::count(),
                'events_approved' => Event::where('status', 'published')->count(),
                'total_revenue' => Order::where('payment_status', 'paid')->sum('total_amount'),
                'commissions_paid' => Commission::where('status', 'paid')->sum('amount'),
            ];

            $recentActivities = collect([
                Event::where('status', 'published')
                    ->latest('updated_at')
                    ->take(5)
                    ->get()
                    ->map(function($event) {
                        return [
                            'type' => 'event_approved',
                            'message' => "Événement '{$event->title}' approuvé",
                            'date' => $event->updated_at,
                            'icon' => 'fas fa-check-circle',
                            'color' => 'success'
                        ];
                    }),
            ])->flatten()->sortByDesc('date')->take(10);

            return view('admin.profile', compact('user', 'activityStats', 'recentActivities'));

        } catch (\Exception $e) {
            \Log::error('Erreur profil admin: ' . $e->getMessage());
            return view('admin.profile', [
                'user' => auth()->user(),
                'activityStats' => [],
                'recentActivities' => collect()
            ]);
        }
    }

    // ================================================================
    // MÉTHODES UTILITAIRES PRIVÉES
    // ================================================================

    /**
     * Statistiques par défaut en cas d'erreur
     */
    private function getDefaultStats()
    {
        return [
            'total_users' => 0,
            'total_events' => 0,
            'total_orders' => 0,
            'total_revenue' => 0,
            'pending_events' => 0,
            'this_month_revenue' => 0,
            'verified_users' => 0,
            'promoteurs_actifs' => 0,
        ];
    }

    /**
     * Helper pour les plages de dates
     */
    private function getDateRange($period)
    {
        switch ($period) {
            case 'today':
                return [now()->startOfDay(), now()->endOfDay()];
            case 'this_week':
                return [now()->startOfWeek(), now()->endOfWeek()];
            case 'this_month':
                return [now()->startOfMonth(), now()->endOfMonth()];
            case 'this_year':
                return [now()->startOfYear(), now()->endOfYear()];
            default:
                return [now()->startOfMonth(), now()->endOfMonth()];
        }
    }

    // ================================================================
    // EXPORTS - CONSERVÉ TEMPORAIREMENT
    // ================================================================

    public function exportUsers(Request $request)
    {
        return redirect()->route('admin.users.export', $request->all());
    }

    public function exportCommissions(Request $request)
    {
        // Logic pour export commissions
        return response()->streamDownload(function() {
            echo "Export commissions - À implémenter";
        }, 'commissions.csv');
    }

    public function exportOrders(Request $request)
    {
        // Logic pour export commandes
        return response()->streamDownload(function() {
            echo "Export commandes - À implémenter";
        }, 'orders.csv');
    }

    public function exportEvents(Request $request)
    {
        // Logic pour export événements
        return response()->streamDownload(function() {
            echo "Export événements - À implémenter";
        }, 'events.csv');
    }

    public function exportTickets(Request $request)
    {
        // Logic pour export tickets
        return response()->streamDownload(function() {
            echo "Export tickets - À implémenter";
        }, 'tickets.csv');
    }

    public function exportAll(Request $request)
    {
        // Logic pour export global
        return response()->streamDownload(function() {
            echo "Export global - À implémenter";
        }, 'export_complet.zip');
    }
}