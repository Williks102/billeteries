<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Event;
use App\Models\Order;
use App\Models\Commission;
use App\Models\EventCategory;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->isAdmin()) {
                abort(403, 'Accès réservé aux administrateurs');
            }
            return $next($request);
        });
    }

    /**
     * Dashboard admin principal
     */
    public function dashboard(Request $request)
    {
        try {
            // Période sélectionnée (par défaut: ce mois)
            $period = $request->get('period', 'this_month');
            $dateRange = $this->getDateRange($period);

            // Statistiques principales
            $stats = $this->getMainStats($dateRange);
            
            // Revenus et commissions
            $revenues = $this->getRevenueStats($dateRange);
            
            // Statistiques par catégorie
            $categoryStats = $this->getCategoryStats($dateRange);
            
            // Top promoteurs
            $topPromoters = $this->getTopPromoters($dateRange);
            
            // Commandes récentes
            $recentOrders = $this->getRecentOrders();
            
            // Données pour graphiques
            $chartData = $this->getChartData($dateRange);
            
            // Alertes et notifications
            $alerts = $this->getAlerts();

            return view('admin.dashboard', compact(
                'stats', 'revenues', 'categoryStats', 'topPromoters', 
                'recentOrders', 'chartData', 'alerts', 'period'
            ));
            
        } catch (\Exception $e) {
            \Log::error('Erreur dashboard admin: ' . $e->getMessage());
            
            // Données par défaut en cas d'erreur
            $stats = $this->getDefaultStats();
            $period = $request->get('period', 'this_month');
            
            return view('admin.dashboard', compact('stats', 'period'))
                ->with('error', 'Erreur lors du chargement du dashboard');
        }
    }

    /**
     * Obtenir la plage de dates selon la période
     */
    private function getDateRange($period)
    {
        switch ($period) {
            case 'today':
                return [
                    'start' => now()->startOfDay(),
                    'end' => now()->endOfDay()
                ];
            case 'this_week':
                return [
                    'start' => now()->startOfWeek(),
                    'end' => now()->endOfWeek()
                ];
            case 'this_month':
                return [
                    'start' => now()->startOfMonth(),
                    'end' => now()->endOfMonth()
                ];
            case 'last_month':
                return [
                    'start' => now()->subMonth()->startOfMonth(),
                    'end' => now()->subMonth()->endOfMonth()
                ];
            case 'this_year':
                return [
                    'start' => now()->startOfYear(),
                    'end' => now()->endOfYear()
                ];
            default:
                return [
                    'start' => now()->startOfMonth(),
                    'end' => now()->endOfMonth()
                ];
        }
    }

    /**
     * Statistiques principales
     */
    private function getMainStats($dateRange)
    {
        // Revenus totaux
        $totalRevenue = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->sum('total_amount');

        // Commissions totales
        $totalCommissions = Commission::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->sum('amount');

        // Billets vendus
        $totalTickets = Ticket::where('status', 'sold')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        // Utilisateurs actifs
        $totalUsers = User::where('role', '!=', 'admin')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        // Commissions en attente
        $pendingCommissions = Commission::where('status', 'pending')->count();

        // Croissance par rapport à la période précédente
        $previousPeriod = $this->getPreviousPeriod($dateRange);
        
        $previousRevenue = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$previousPeriod['start'], $previousPeriod['end']])
            ->sum('total_amount');

        $previousTickets = Ticket::where('status', 'sold')
            ->whereBetween('created_at', [$previousPeriod['start'], $previousPeriod['end']])
            ->count();

        $revenueGrowth = $previousRevenue > 0 ? 
            (($totalRevenue - $previousRevenue) / $previousRevenue) * 100 : 0;
        
        $ticketsGrowth = $previousTickets > 0 ? 
            (($totalTickets - $previousTickets) / $previousTickets) * 100 : 0;

        return [
            'total_revenue' => $totalRevenue,
            'total_commissions' => $totalCommissions,
            'total_tickets' => $totalTickets,
            'total_users' => $totalUsers,
            'pending_commissions' => $pendingCommissions,
            'revenue_growth' => round($revenueGrowth, 1),
            'tickets_growth' => round($ticketsGrowth, 1),
            'new_users' => User::where('role', '!=', 'admin')
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->count()
        ];
    }

    /**
     * Statistiques de revenus
     */
    private function getRevenueStats($dateRange)
    {
        return [
            'total_revenue' => Order::where('payment_status', 'paid')
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->sum('total_amount'),
            'platform_commission' => Commission::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->sum('amount'),
            'promoters_revenue' => Order::where('payment_status', 'paid')
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->sum('net_amount')
        ];
    }

    /**
     * Statistiques par catégorie
     */
    private function getCategoryStats($dateRange)
    {
        return EventCategory::withCount(['events' => function($query) use ($dateRange) {
                $query->where('status', 'published')
                      ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
            }])
            ->with(['events' => function($query) use ($dateRange) {
                $query->withCount(['ticketTypes as tickets_sold' => function($subQuery) use ($dateRange) {
                    $subQuery->join('tickets', 'ticket_types.id', '=', 'tickets.ticket_type_id')
                             ->where('tickets.status', 'sold')
                             ->whereBetween('tickets.created_at', [$dateRange['start'], $dateRange['end']]);
                }]);
            }])
            ->get()
            ->map(function($category) {
                $category->tickets_sold = $category->events->sum('tickets_sold');
                return $category;
            });
    }

    /**
     * Top promoteurs
     */
    private function getTopPromoters($dateRange)
    {
        return User::where('role', 'promoteur')
            ->withCount(['events' => function($query) use ($dateRange) {
                $query->where('status', 'published')
                      ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
            }])
            ->with(['events' => function($query) use ($dateRange) {
                $query->with(['orders' => function($subQuery) use ($dateRange) {
                    $subQuery->where('payment_status', 'paid')
                             ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
                }]);
            }])
            ->get()
            ->map(function($promoter) {
                $promoter->total_revenue = $promoter->events->sum(function($event) {
                    return $event->orders->sum('total_amount');
                });
                return $promoter;
            })
            ->sortByDesc('total_revenue')
            ->take(10);
    }

    /**
     * Commandes récentes
     */
    private function getRecentOrders()
    {
        return Order::with(['user', 'event'])
            ->latest()
            ->limit(10)
            ->get();
    }

    /**
     * Données pour les graphiques
     */
    private function getChartData($dateRange)
    {
        $days = collect();
        $current = $dateRange['start']->copy();
        
        while ($current->lte($dateRange['end'])) {
            $days->push($current->copy());
            $current->addDay();
        }

        $revenueData = $days->map(function($date) {
            return Order::where('payment_status', 'paid')
                ->whereDate('created_at', $date)
                ->sum('total_amount');
        });

        return [
            'labels' => $days->map(function($date) {
                return $date->format('d/m');
            })->toArray(),
            'revenue' => $revenueData->toArray()
        ];
    }

    /**
     * Alertes et notifications
     */
    private function getAlerts()
    {
        $alerts = collect();

        // Commissions en attente
        $pendingCommissions = Commission::where('status', 'pending')->count();
        if ($pendingCommissions > 0) {
            $alerts->push("$pendingCommissions commissions en attente de paiement");
        }

        // Événements sans ventes
        $eventsNoSales = Event::where('status', 'published')
            ->where('event_date', '>=', now())
            ->whereDoesntHave('ticketTypes.tickets', function($query) {
                $query->where('status', 'sold');
            })
            ->count();
        
        if ($eventsNoSales > 0) {
            $alerts->push("$eventsNoSales événements publiés sans ventes");
        }

        // Promoteurs inactifs
        $inactivePromoters = User::where('role', 'promoteur')
            ->whereDoesntHave('events', function($query) {
                $query->where('created_at', '>=', now()->subDays(30));
            })
            ->count();
        
        if ($inactivePromoters > 0) {
            $alerts->push("$inactivePromoters promoteurs inactifs depuis 30 jours");
        }

        return $alerts;
    }

    /**
     * Période précédente pour calcul de croissance
     */
    private function getPreviousPeriod($dateRange)
    {
        $duration = $dateRange['end']->diffInDays($dateRange['start']);
        
        return [
            'start' => $dateRange['start']->copy()->subDays($duration + 1),
            'end' => $dateRange['start']->copy()->subDay()
        ];
    }

    /**
     * Statistiques par défaut en cas d'erreur
     */
    private function getDefaultStats()
    {
        return [
            'total_revenue' => 0,
            'total_commissions' => 0,
            'total_tickets' => 0,
            'total_users' => 0,
            'pending_commissions' => 0,
            'revenue_growth' => 0,
            'tickets_growth' => 0,
            'new_users' => 0
        ];
    }

    /**
     * Liste des utilisateurs
     */
    public function users(Request $request)
    {
        $query = User::query();
        
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }
        
        $users = $query->latest()->paginate(20);
        
        return view('admin.users', compact('users'));
    }

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
     * Liste des commandes
     */
    public function orders(Request $request)
    {
        $query = Order::with(['user', 'event', 'orderItems']);
        
        if ($request->filled('status')) {
            $query->where('payment_status', $request->status);
        }
        
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('order_number', 'like', '%' . $request->search . '%')
                  ->orWhereHas('user', function($subQ) use ($request) {
                      $subQ->where('name', 'like', '%' . $request->search . '%')
                           ->orWhere('email', 'like', '%' . $request->search . '%');
                  });
            });
        }
        
        $orders = $query->latest()->paginate(20);
        
        return view('admin.orders', compact('orders'));
    }

    /**
     * Détail d'une commande
     */
    public function orderDetail(Order $order)
    {
        $order->load(['user', 'event', 'orderItems.ticketType', 'tickets']);
        
        return view('admin.order-detail', compact('order'));
    }

    /**
     * Gestion des commissions
     */
    public function commissions(Request $request)
    {
        $query = Commission::with(['promoter', 'order']);
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        $commissions = $query->latest()->paginate(20);
        
        return view('admin.commissions', compact('commissions'));
    }

    /**
     * Payer une commission
     */
    public function payCommission(Commission $commission)
    {
        $commission->update([
            'status' => 'paid',
            'paid_at' => now()
        ]);
        
        return redirect()->back()
            ->with('success', 'Commission payée avec succès');
    }

    /**
     * Export des données
     */
    public function exportCommissions()
    {
        // Logique d'export des commissions
        return response()->download(/* fichier export */);
    }

    public function exportRevenues($period)
    {
        // Logique d'export des revenus
        return response()->download(/* fichier export */);
    }

    public function exportOrders()
    {
        // Logique d'export des commandes
        return response()->download(/* fichier export */);
    }

    public function exportPromoters()
    {
        // Logique d'export des promoteurs
        return response()->download(/* fichier export */);
    }

    public function exportAccounting($period)
    {
        // Logique d'export comptabilité
        return response()->download(/* fichier export */);
    }

    /**
     * Détail d'un événement
     */
    public function eventDetail($id)
    {
        $event = Event::with(['category', 'promoteur', 'ticketTypes.tickets', 'orders'])
            ->findOrFail($id);
        
        return view('admin.event-detail', compact('event'));
    }
}