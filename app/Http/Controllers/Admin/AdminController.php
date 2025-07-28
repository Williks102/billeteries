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
use Illuminate\Support\Facades\Hash;

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

        // Statistiques principales avec vérifications
        $stats = [
            'total_users' => User::count(),
            'total_events' => Event::count(),
            'total_orders' => Order::where('payment_status', 'paid')->count(),
            'total_revenue' => Order::where('payment_status', 'paid')->sum('total_amount') ?? 0,
            'pending_commissions' => Commission::where('status', 'pending')->count(),
            'active_promoters' => User::where('role', 'promoteur')->whereHas('events')->count(),
        ];
        
        // Commandes récentes avec vérifications
        $recentOrders = Order::with(['user', 'event'])
            ->where('payment_status', 'paid')
            ->latest()
            ->limit(10)
            ->get();
        
        // Données pour graphiques (éviter les erreurs JS)
        $chartData = [
            'labels' => ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun'],
            'revenue' => [0, 0, 0, 0, 0, 0],
            'orders' => [0, 0, 0, 0, 0, 0]
        ];
        
        // Alertes système
        $alerts = [];
        
        // Vérifier les commissions en attente
        $pendingCommissions = Commission::where('status', 'pending')->count();
        if ($pendingCommissions > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'clock',
                'message' => "$pendingCommissions commissions en attente de paiement"
            ];
        }

        return view('admin.dashboard', compact(
            'stats', 'recentOrders', 'chartData', 'alerts', 'period'
        ));
        
    } catch (\Exception $e) {
        \Log::error('Erreur dashboard admin: ' . $e->getMessage());
        
        // Données par défaut en cas d'erreur
        $stats = [
            'total_users' => 0,
            'total_events' => 0,
            'total_orders' => 0,
            'total_revenue' => 0,
            'pending_commissions' => 0,
            'active_promoters' => 0,
        ];
        
        $recentOrders = collect();
        $chartData = ['labels' => [], 'revenue' => [], 'orders' => []];
        $alerts = [];
        $period = 'this_month';
        
        return view('admin.dashboard', compact('stats', 'recentOrders', 'chartData', 'alerts', 'period'))
            ->with('error', 'Erreur lors du chargement des données du dashboard');
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
        default:
            return [
                'start' => now()->startOfMonth(),
                'end' => now()->endOfMonth()
            ];
        case 'this_year':
            return [
                'start' => now()->startOfYear(),
                'end' => now()->endOfYear()
            ];
    }
}

private function exportToCSV($data, $filename)
{
    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
    ];
    
    $callback = function() use ($data) {
        $file = fopen('php://output', 'w');
        
        // En-têtes
        fputcsv($file, ['Rapport Financier - ' . $data['period']]);
        fputcsv($file, ['Généré le: ' . now()->format('d/m/Y H:i')]);
        fputcsv($file, []);
        
        // Résumé
        fputcsv($file, ['RÉSUMÉ']);
        fputcsv($file, ['Total Revenus', number_format($data['summary']['total_revenue']) . ' FCFA']);
        fputcsv($file, ['Total Commissions', number_format($data['summary']['total_commissions']) . ' FCFA']);
        fputcsv($file, ['Revenus Net', number_format($data['summary']['net_revenue']) . ' FCFA']);
        fputcsv($file, ['Nombre Commandes', $data['summary']['orders_count']]);
        fputcsv($file, []);
        
        // Détail commandes
        fputcsv($file, ['DÉTAIL COMMANDES']);
        fputcsv($file, ['Date', 'Client', 'Événement', 'Montant', 'Statut']);
        
        foreach ($data['orders'] as $order) {
            fputcsv($file, [
                $order->created_at->format('d/m/Y'),
                $order->user->name ?? 'N/A',
                $order->event->title ?? 'N/A',
                number_format($order->total_amount) . ' FCFA',
                $order->payment_status
            ]);
        }
        
        fputcsv($file, []);
        
        // Détail commissions
        fputcsv($file, ['DÉTAIL COMMISSIONS']);
        fputcsv($file, ['Date', 'Promoteur', 'Montant Brut', 'Commission', 'Net', 'Statut']);
        
        foreach ($data['commissions'] as $commission) {
            fputcsv($file, [
                $commission->created_at->format('d/m/Y'),
                $commission->promoter->name ?? 'N/A',
                number_format($commission->gross_amount) . ' FCFA',
                number_format($commission->commission_amount) . ' FCFA',
                number_format($commission->net_amount) . ' FCFA',
                $commission->status
            ]);
        }
        
        fclose($file);
    };
    
    return response()->stream($callback, 200, $headers);
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
 * Export global - toutes les données
 */
public function exportAll(Request $request)
{
    $format = $request->get('format', 'excel');
    $dataType = $request->get('data_type', 'all');
    
    try {
        switch ($dataType) {
            case 'financial':
                return $this->exportFinancial($request);
            case 'users':
                return $this->exportUsers($request);
            case 'events':
                return $this->exportEvents($request);
            case 'orders':
                return $this->exportOrders($request);
            case 'tickets':
                return $this->exportTickets($request);
            case 'all':
            default:
                return $this->exportCompleteData($request);
        }
    } catch (\Exception $e) {
        \Log::error('Erreur export global: ' . $e->getMessage());
        return redirect()->back()
            ->with('error', 'Erreur lors de l\'export: ' . $e->getMessage());
    }
}

/**
 * Export de toutes les données
 */
private function exportCompleteData(Request $request)
{
    $filename = 'export_complet_' . now()->format('Y-m-d') . '.csv';
    
    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
    ];
    
    $callback = function() {
        $file = fopen('php://output', 'w');
        
        // Export résumé de toutes les données
        fputcsv($file, ['=== RÉSUMÉ GÉNÉRAL ===']);
        fputcsv($file, ['Type', 'Nombre total', 'Dernière mise à jour']);
        fputcsv($file, ['Utilisateurs', User::count(), User::latest()->first()?->updated_at ?? 'N/A']);
        fputcsv($file, ['Événements', Event::count(), Event::latest()->first()?->updated_at ?? 'N/A']);
        fputcsv($file, ['Commandes', Order::count(), Order::latest()->first()?->updated_at ?? 'N/A']);
        fputcsv($file, ['Commissions', Commission::count(), Commission::latest()->first()?->updated_at ?? 'N/A']);
        fputcsv($file, []);
        
        fclose($file);
    };
    
    return response()->stream($callback, 200, $headers);
}

/**
 * Export des billets
 */
public function exportTickets(Request $request)
{
    $format = $request->get('format', 'csv');
    
    $tickets = Ticket::with(['user', 'ticketType.event'])->get();
    
    $filename = 'billets_' . now()->format('Y-m-d') . '.' . $format;
    
    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
    ];
    
    $callback = function() use ($tickets) {
        $file = fopen('php://output', 'w');
        
        fputcsv($file, [
            'Code Billet', 'Événement', 'Type', 'Acheteur', 
            'Statut', 'Prix', 'Date Achat', 'Date Utilisation'
        ]);
        
        foreach ($tickets as $ticket) {
            fputcsv($file, [
                $ticket->ticket_code,
                $ticket->ticketType->event->title ?? 'N/A',
                $ticket->ticketType->name ?? 'N/A',
                $ticket->user->name ?? 'N/A',
                $ticket->status,
                $ticket->ticketType->price ?? 0,
                $ticket->created_at->format('d/m/Y'),
                $ticket->used_at ? $ticket->used_at->format('d/m/Y H:i') : 'Non utilisé'
            ]);
        }
        
        fclose($file);
    };
    
    return response()->stream($callback, 200, $headers);
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
 * === MÉTHODES MANQUANTES POUR EXPORTS ===
 */

public function exportFinancial(Request $request)
{
    $format = $request->get('format', 'excel');
    $period = $request->get('period', 'this_month');
    
    try {
        $dateRange = $this->getDateRange($period);
        
        // Collecte des données financières
        $orders = Order::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->where('payment_status', 'paid')
            ->with(['user', 'event'])
            ->get();
            
        $commissions = Commission::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->with(['promoter', 'order.event'])
            ->get();
            
        $totalRevenue = $orders->sum('total_amount');
        $totalCommissions = $commissions->sum('commission_amount');
        $netRevenue = $totalRevenue - $totalCommissions;
        
        $data = [
            'period' => $period,
            'date_range' => $dateRange,
            'orders' => $orders,
            'commissions' => $commissions,
            'summary' => [
                'total_revenue' => $totalRevenue,
                'total_commissions' => $totalCommissions,
                'net_revenue' => $netRevenue,
                'orders_count' => $orders->count(),
                'average_order' => $orders->count() > 0 ? $totalRevenue / $orders->count() : 0
            ]
        ];
        
        $filename = 'rapport_financier_' . $period . '_' . now()->format('Y-m-d');
        
        if ($format === 'excel') {
            // Si vous avez Laravel Excel installé
            // return Excel::download(new FinancialReportExport($data), $filename . '.xlsx');
            
            // Sinon, export CSV simple
            return $this->exportToCSV($data, $filename . '.csv');
        } elseif ($format === 'pdf') {
            // Si vous avez DomPDF installé
            // $pdf = PDF::loadView('admin.exports.financial-pdf', $data);
            // return $pdf->download($filename . '.pdf');
            
            return $this->exportToCSV($data, $filename . '.csv');
        }
        
    } catch (\Exception $e) {
        \Log::error('Erreur export financier: ' . $e->getMessage());
        return redirect()->back()
            ->with('error', 'Erreur lors de l\'export: ' . $e->getMessage());
    }
}

public function exportUsers(Request $request)
{
    $format = $request->get('format', 'csv');
    
    $users = User::with(['events', 'orders'])->get();
    
    $filename = 'utilisateurs_' . now()->format('Y-m-d') . '.' . $format;
    
    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
    ];
    
    $callback = function() use ($users) {
        $file = fopen('php://output', 'w');
        
        // En-têtes CSV
        fputcsv($file, [
            'ID', 'Nom', 'Email', 'Rôle', 'Téléphone', 
            'Email vérifié', 'Date inscription', 'Nb événements', 'Nb commandes'
        ]);
        
        // Données
        foreach ($users as $user) {
            fputcsv($file, [
                $user->id,
                $user->name,
                $user->email,
                $user->role,
                $user->phone,
                $user->email_verified_at ? 'Oui' : 'Non',
                $user->created_at->format('d/m/Y'),
                $user->events ? $user->events->count() : 0,
                $user->orders ? $user->orders->count() : 0
            ]);
        }
        
        fclose($file);
    };
    
    return response()->stream($callback, 200, $headers);
}

public function exportEvents(Request $request)
{
    $format = $request->get('format', 'csv');
    
    $events = Event::with(['promoteur', 'category', 'orders', 'ticketTypes'])
        ->withCount(['orders', 'tickets'])
        ->get();
    
    $filename = 'evenements_' . now()->format('Y-m-d') . '.' . $format;
    
    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
    ];
    
    $callback = function() use ($events) {
        $file = fopen('php://output', 'w');
        
        fputcsv($file, [
            'ID', 'Titre', 'Promoteur', 'Catégorie', 'Date événement',
            'Statut', 'Prix min', 'Prix max', 'Nb commandes', 'Nb billets vendus'
        ]);
        
        foreach ($events as $event) {
            $prices = $event->ticketTypes->pluck('price');
            
            fputcsv($file, [
                $event->id,
                $event->title,
                $event->promoteur->name ?? 'N/A',
                $event->category->name ?? 'N/A',
                $event->event_date->format('d/m/Y H:i'),
                $event->status,
                $prices->min() ?? 0,
                $prices->max() ?? 0,
                $event->orders_count,
                $event->tickets_count
            ]);
        }
        
        fclose($file);
    };
    
    return response()->stream($callback, 200, $headers);
}

public function exportOrders(Request $request)
{
    $orders = Order::with(['user', 'event', 'orderItems'])
        ->latest()
        ->get();
    
    $filename = 'commandes_' . now()->format('Y-m-d') . '.csv';
    
    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
    ];
    
    $callback = function() use ($orders) {
        $file = fopen('php://output', 'w');
        
        fputcsv($file, [
            'N° Commande', 'Client', 'Email', 'Événement',
            'Montant', 'Statut', 'Date', 'Quantité billets'
        ]);
        
        foreach ($orders as $order) {
            fputcsv($file, [
                $order->order_number ?? $order->id,
                $order->user->name ?? 'N/A',
                $order->user->email ?? 'N/A',
                $order->event->title ?? 'N/A',
                $order->total_amount,
                $order->payment_status,
                $order->created_at->format('d/m/Y H:i'),
                $order->orderItems->sum('quantity')
            ]);
        }
        
        fclose($file);
    };
    
    return response()->stream($callback, 200, $headers);
}

public function exportCommissions(Request $request)
{
    $commissions = Commission::with(['promoter', 'order.event'])
        ->latest()
        ->get();
    
    $filename = 'commissions_' . now()->format('Y-m-d') . '.csv';
    
    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => "attachment; filename=\"$filename\"",
    ];
    
    $callback = function() use ($commissions) {
        $file = fopen('php://output', 'w');
        
        fputcsv($file, [
            'Date', 'Promoteur', 'Événement', 'N° Commande',
            'Montant brut', 'Taux %', 'Commission', 'Net promoteur', 'Statut'
        ]);
        
        foreach ($commissions as $commission) {
            fputcsv($file, [
                $commission->created_at->format('d/m/Y'),
                $commission->promoter->name ?? 'N/A',
                $commission->order->event->title ?? 'N/A',
                $commission->order->order_number ?? $commission->order->id,
                $commission->gross_amount,
                $commission->commission_rate,
                $commission->commission_amount,
                $commission->net_amount,
                $commission->status
            ]);
        }
        
        fclose($file);
    };
    
    return response()->stream($callback, 200, $headers);
}

/**
 * === MÉTHODE POUR RAPPORTS ===
 */

public function reports(Request $request)
{
    $period = $request->get('period', 'this_month');
    $dateRange = $this->getDateRange($period);
    
    // Statistiques du mois
    $monthlyRevenue = Order::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
        ->where('payment_status', 'paid')
        ->sum('total_amount');
        
    $monthlyOrders = Order::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
        ->where('payment_status', 'paid')
        ->count();
        
    $monthlyEvents = Event::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
        ->where('status', 'published')
        ->count();
        
    $monthlyUsers = User::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
        ->count();
    
    return view('admin.reports', compact(
        'monthlyRevenue', 'monthlyOrders', 'monthlyEvents', 'monthlyUsers', 'period'
    ));
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
 * === MÉTHODES MANQUANTES POUR GESTION UTILISATEURS ===
 */

public function createUser()
{
    return view('admin.users.create');
}

public function storeUser(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8|confirmed',
        'role' => 'required|in:admin,promoteur,acheteur',
        'phone' => 'nullable|string|max:20',
    ]);

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'role' => $request->role,
        'phone' => $request->phone,
        'email_verified_at' => now(), // Auto-vérifier les comptes créés par admin
    ]);

    return redirect()->route('admin.users')
        ->with('success', 'Utilisateur créé avec succès');
}

public function editUser(User $user)
{
    return view('admin.users.edit', compact('user'));
}

public function updateUser(Request $request, User $user)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
        'role' => 'required|in:admin,promoteur,acheteur',
        'phone' => 'nullable|string|max:20',
    ]);

    $user->update($request->only(['name', 'email', 'role', 'phone']));

    return redirect()->route('admin.users')
        ->with('success', 'Utilisateur mis à jour avec succès');
}

public function destroyUser(User $user)
{
    // Empêcher la suppression de son propre compte
    if ($user->id === auth()->id()) {
        return response()->json(['success' => false, 'message' => 'Vous ne pouvez pas supprimer votre propre compte']);
    }

    $user->delete();

    return response()->json(['success' => true, 'message' => 'Utilisateur supprimé avec succès']);
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

   

    public function exportRevenues($period)
    {
        // Logique d'export des revenus
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

    
public function updateOrderStatus(Order $order, Request $request) {
    $order->update(['payment_status' => $request->status]);
    return response()->json(['success' => true]);
}

public function bulkUpdateOrders(Request $request) {
    Order::whereIn('id', $request->order_ids)
         ->update(['payment_status' => $request->status]);
    return response()->json(['success' => true]);
}


}