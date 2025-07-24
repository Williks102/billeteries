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
    }

public function users()
{
    $users = User::latest()->get();
    return view('admin.users', compact('users'));
}

public function events()
{
    $events = Event::with('promoteur')->latest()->get();
    return view('admin.events', compact('events'));
}

public function eventDetail($id)
{
    $event = Event::with(['promoteur', 'orders.tickets'])
        ->withCount(['orders as tickets_sold' => function ($query) {
            $query->where('payment_status', 'paid');
        }])
        ->findOrFail($id);

    $orders = $event->orders->filter(function ($order) {
        return $order->payment_status === 'paid';
    });

    $totalRevenue = $orders->sum('total_amount');
    $totalTickets = $orders->sum(function ($order) {
        return $order->tickets->count();
    });

    return view('admin.event-detail', compact('event', 'totalRevenue', 'totalTickets', 'orders'));
}


public function orders()
{
    $orders = Order::with(['user', 'event'])->latest()->get();
    return view('admin.orders', compact('orders'));
}

public function orderDetail($id)
{
    $order = Order::with(['user', 'event', 'tickets'])->findOrFail($id);
    return view('admin.order-detail', compact('order'));
}



    /**
     * Statistiques principales de la plateforme
     */
    private function getMainStats($dateRange)
    {
        $totalUsers = User::count();
        $newUsers = User::whereBetween('created_at', $dateRange)->count();
        
        $totalEvents = Event::count();
        $newEvents = Event::whereBetween('created_at', $dateRange)->count();
        
        $totalOrders = Order::where('payment_status', 'paid')->count();
        $newOrders = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', $dateRange)->count();
        
        $totalTickets = Order::where('payment_status', 'paid')
            ->withCount('tickets')->get()->sum('tickets_count');
        $newTickets = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', $dateRange)
            ->withCount('tickets')->get()->sum('tickets_count');

        return [
            'total_users' => $totalUsers,
            'new_users' => $newUsers,
            'users_growth' => $this->calculateGrowth($totalUsers, $newUsers),
            
            'total_events' => $totalEvents,
            'new_events' => $newEvents,
            'events_growth' => $this->calculateGrowth($totalEvents, $newEvents),
            
            'total_orders' => $totalOrders,
            'new_orders' => $newOrders,
            'orders_growth' => $this->calculateGrowth($totalOrders, $newOrders),
            
            'total_tickets' => $totalTickets,
            'new_tickets' => $newTickets,
            'tickets_growth' => $this->calculateGrowth($totalTickets, $newTickets),
        ];
    }

    /**
     * Statistiques de revenus et commissions
     */
    private function getRevenueStats($dateRange)
    {
        // Revenus bruts (total des commandes payées)
        $totalRevenue = Order::where('payment_status', 'paid')->sum('total_amount');
        $periodRevenue = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', $dateRange)->sum('total_amount');

        // Commissions de la plateforme
        $totalCommissions = Commission::sum('commission_amount');
        $periodCommissions = Commission::whereBetween('created_at', $dateRange)
            ->sum('commission_amount');

        // Revenus nets des promoteurs
        $totalPromoterRevenue = Commission::sum('net_amount');
        $periodPromoterRevenue = Commission::whereBetween('created_at', $dateRange)
            ->sum('net_amount');

        // Commissions en attente de paiement
        $pendingCommissions = Commission::where('status', 'pending')->sum('net_amount');
        
        // Frais de plateforme
        $totalPlatformFees = Commission::sum('platform_fee');
        $periodPlatformFees = Commission::whereBetween('created_at', $dateRange)
            ->sum('platform_fee');

        // Panier moyen
        $averageOrderValue = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', $dateRange)
            ->avg('total_amount');

        return [
            'total_revenue' => $totalRevenue,
            'period_revenue' => $periodRevenue,
            'revenue_growth' => $this->calculateGrowth($totalRevenue, $periodRevenue),
            
            'total_commissions' => $totalCommissions,
            'period_commissions' => $periodCommissions,
            'commission_growth' => $this->calculateGrowth($totalCommissions, $periodCommissions),
            
            'total_promoter_revenue' => $totalPromoterRevenue,
            'period_promoter_revenue' => $periodPromoterRevenue,
            
            'pending_commissions' => $pendingCommissions,
            'pending_commissions_count' => Commission::where('status', 'pending')->count(),
            
            'total_platform_fees' => $totalPlatformFees,
            'period_platform_fees' => $periodPlatformFees,
            
            'average_order_value' => $averageOrderValue,
            'commission_rate' => $totalRevenue > 0 ? ($totalCommissions / $totalRevenue) * 100 : 0,
        ];
    }

    /**
     * Statistiques par catégorie d'événements
     */
    private function getCategoryStats($dateRange)
    {
        return EventCategory::withCount(['events' => function ($query) use ($dateRange) {
                $query->whereBetween('created_at', $dateRange);
            }])
            ->with(['events' => function ($query) use ($dateRange) {
                $query->whereBetween('created_at', $dateRange)
                      ->withSum(['orders as total_revenue' => function ($q) {
                          $q->where('payment_status', 'paid');
                      }], 'total_amount');
            }])
            ->get()
            ->map(function ($category) {
                $category->total_revenue = $category->events->sum('total_revenue') ?? 0;
                $category->total_tickets = $category->events->sum(function ($event) {
                    return $event->orders()->where('payment_status', 'paid')->withCount('tickets')->get()->sum('tickets_count');
                });
                return $category;
            })
            ->sortByDesc('total_revenue');
    }

    /**
     * Top promoteurs par revenus
     */
    private function getTopPromoters($dateRange)
    {
        return User::where('role', 'promoteur')
            ->withCount(['events as events_count' => function ($query) use ($dateRange) {
                $query->whereBetween('created_at', $dateRange);
            }])
            ->withSum(['commissions as total_earned' => function ($query) use ($dateRange) {
                $query->whereBetween('created_at', $dateRange);
            }], 'net_amount')
            ->withSum(['commissions as platform_earned' => function ($query) use ($dateRange) {
                $query->whereBetween('created_at', $dateRange);
            }], 'commission_amount')
            ->having('total_earned', '>', 0)
            ->orderByDesc('total_earned')
            ->limit(10)
            ->get();
    }

    /**
     * Commandes récentes pour supervision
     */
    private function getRecentOrders()
    {
        return Order::with(['user', 'event.category', 'event.promoteur'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Données pour les graphiques
     */
    private function getChartData($dateRange)
    {
        // Revenus par jour sur la période
        $dailyRevenues = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', $dateRange)
            ->selectRaw('DATE(created_at) as date, SUM(total_amount) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Commissions par jour
        $dailyCommissions = Commission::whereBetween('created_at', $dateRange)
            ->selectRaw('DATE(created_at) as date, SUM(commission_amount) as commission')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Nouvelles inscriptions par jour
        $dailyUsers = User::whereBetween('created_at', $dateRange)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as users')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'daily_revenues' => $dailyRevenues,
            'daily_commissions' => $dailyCommissions,
            'daily_users' => $dailyUsers,
        ];
    }

    /**
     * Alertes et notifications importantes
     */
    private function getAlerts()
    {
        $alerts = [];

        // Commissions en attente importantes
        $highPendingCommissions = Commission::where('status', 'pending')
            ->where('net_amount', '>', 100000) // Plus de 100k FCFA
            ->count();
        
        if ($highPendingCommissions > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'fas fa-money-bill-wave',
                'message' => "$highPendingCommissions commission(s) importante(s) en attente de paiement",
                'action' => route('admin.commissions.pending')
            ];
        }

        // Événements sans ventes
        $eventsWithoutSales = Event::where('status', 'published')
            ->where('event_date', '>', now())
            ->whereDoesntHave('orders', function ($query) {
                $query->where('payment_status', 'paid');
            })
            ->where('created_at', '<', now()->subDays(7))
            ->count();

        if ($eventsWithoutSales > 0) {
            $alerts[] = [
                'type' => 'info',
                'icon' => 'fas fa-chart-line',
                'message' => "$eventsWithoutSales événement(s) sans vente depuis 7 jours",
                'action' => route('admin.events.no-sales')
            ];
        }

        // Promoteurs inactifs
        $inactivePromoters = User::where('role', 'promoteur')
            ->whereDoesntHave('events', function ($query) {
                $query->where('created_at', '>', now()->subMonth());
            })
            ->count();

        if ($inactivePromoters > 5) {
            $alerts[] = [
                'type' => 'secondary',
                'icon' => 'fas fa-user-clock',
                'message' => "$inactivePromoters promoteur(s) inactif(s) ce mois",
                'action' => route('admin.promoters.inactive')
            ];
        }

        return $alerts;
    }

    /**
     * Calculer la croissance en pourcentage
     */
    private function calculateGrowth($total, $period)
    {
        if ($total == 0) return 0;
        $previous = $total - $period;
        if ($previous == 0) return 100;
        return round((($period - $previous) / $previous) * 100, 1);
    }

    /**
     * Obtenir la plage de dates selon la période
     */
    private function getDateRange($period)
    {
        switch ($period) {
            case 'today':
                return [now()->startOfDay(), now()->endOfDay()];
            case 'yesterday':
                return [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()];
            case 'this_week':
                return [now()->startOfWeek(), now()->endOfWeek()];
            case 'last_week':
                return [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()];
            case 'this_month':
                return [now()->startOfMonth(), now()->endOfMonth()];
            case 'last_month':
                return [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()];
            case 'this_year':
                return [now()->startOfYear(), now()->endOfYear()];
            case 'last_year':
                return [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()];
            default:
                return [now()->startOfMonth(), now()->endOfMonth()];
        }
    }

    /**
     * Gestion des commissions
     */
    public function commissions(Request $request)
    {
        $status = $request->get('status', 'all');
        
        $query = Commission::with(['order.event', 'promoteur']);
        
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        
        $commissions = $query->orderBy('created_at', 'desc')->paginate(20);
        
        $summary = [
            'total_pending' => Commission::where('status', 'pending')->sum('net_amount'),
            'total_paid' => Commission::where('status', 'paid')->sum('net_amount'),
            'total_held' => Commission::where('status', 'held')->sum('net_amount'),
            'platform_total' => Commission::sum('commission_amount'),
        ];
        
        return view('admin.commissions.index', compact('commissions', 'summary', 'status'));
    }

    /**
     * Payer une commission
     */
    public function payCommission(Commission $commission)
    {
        if ($commission->status !== 'pending') {
            return back()->with('error', 'Cette commission ne peut pas être payée.');
        }
        
        $commission->markAsPaid();
        
        return back()->with('success', "Commission de {$commission->formatted_net_amount} payée à {$commission->promoteur->name}");
    }

    /**
     * EXPORTS EXCEL
     */
    
    /**
     * Export des commissions
     */
    public function exportCommissions(Request $request)
    {
        $filters = $request->only(['status', 'promoteur_id', 'start_date', 'end_date']);
        
        $exportService = app(\App\Services\ExcelExportService::class);
        return $exportService->exportCommissions($filters);
    }
    
    /**
     * Export des revenus
     */
    public function exportRevenues(Request $request)
    {
        $period = $request->get('period', 'this_month');
        $dateRange = $this->getDateRange($period);
        
        $exportService = app(\App\Services\ExcelExportService::class);
        return $exportService->exportRevenues($dateRange[0], $dateRange[1]);
    }
    
    /**
     * Export des commandes
     */
    public function exportOrders(Request $request)
    {
        $filters = $request->only(['status', 'start_date', 'end_date']);
        
        $exportService = app(\App\Services\ExcelExportService::class);
        return $exportService->exportOrders($filters);
    }
    
    /**
     * Export des promoteurs
     */
    public function exportPromoters(Request $request)
    {
        $period = $request->get('period', 'this_month');
        
        $exportService = app(\App\Services\ExcelExportService::class);
        return $exportService->exportPromoters($period);
    }
    
    /**
     * Export comptable complet
     */
    public function exportAccounting(Request $request)
    {
        $period = $request->get('period', 'this_month');
        $dateRange = $this->getDateRange($period);
        
        $exportService = app(\App\Services\ExcelExportService::class);
        return $exportService->exportAccountingReport($dateRange[0], $dateRange[1]);
    }
}