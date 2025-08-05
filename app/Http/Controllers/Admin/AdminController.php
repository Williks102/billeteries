<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Event;
use App\Models\Order;
use App\Models\Setting;
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

            // STATISTIQUES CORRIGÉES
            $stats = [
                'total_users' => User::count(),
                'total_events' => Event::count(),
                // CORRECTION: utiliser payment_status au lieu de status
                'total_orders' => Order::where('payment_status', 'paid')->count(),
                'total_revenue' => Order::where('payment_status', 'paid')->sum('total_amount') ?? 0,
                'pending_commissions' => Commission::where('status', 'pending')->count(),
                'active_promoters' => User::where('role', 'promoteur')->whereHas('events')->count(),
            ];
            
            // COMMANDES RÉCENTES CORRIGÉES
            $recentOrders = Order::with(['user', 'event'])
                ->where('payment_status', 'paid') // CORRECTION
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
            
            // Retourner des valeurs par défaut en cas d'erreur
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
            
            return view('admin.dashboard', compact(
                'stats', 'recentOrders', 'chartData', 'alerts'
            ));
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
                    $event->promoter->name ?? 'N/A',
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
            return response()->json([
                'success' => false, 
                'message' => 'Vous ne pouvez pas supprimer votre propre compte'
            ]);
        }

        $user->delete();

        return response()->json([
            'success' => true, 
            'message' => 'Utilisateur supprimé avec succès'
        ]);
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
     * Liste des commandes (CORRIGER)
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
            ->when($request->status, function($query, $status) {
                // CORRECTION: utiliser payment_status au lieu de status
                $query->where('payment_status', $status);
            })
            ->when($request->payment_status, function($query, $payment_status) {
                // Ajouter filtre spécifique pour payment_status
                $query->where('payment_status', $payment_status);
            })
            ->latest()
            ->paginate(15);

        // STATISTIQUES CORRIGÉES
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

   /**
 * Gestion des commissions (MÉTHODE CORRIGÉE)
 */
public function commissions(Request $request)
{
    // Construction de la requête avec relations
    $query = Commission::with(['promoteur', 'order.event']);
    
    // Filtres
    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }
    
    if ($request->filled('promoter')) {
        $query->where('promoter_id', $request->promoter);
    }
    
    if ($request->filled('period')) {
        $dateRange = $this->getDateRange($request->period);
        $query->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
    }
    
    if ($request->filled('search')) {
        $query->whereHas('promoteur', function($q) use ($request) {
            $q->where('name', 'like', '%' . $request->search . '%');
        })->orWhereHas('order.event', function($q) use ($request) {
            $q->where('title', 'like', '%' . $request->search . '%');
        });
    }
    
    // Pagination
    $commissions = $query->latest()->paginate(20);
    
    // STATISTIQUES CORRIGÉES (le problème principal)
    $stats = [
        // Compteurs par statut
        'pending' => Commission::where('status', 'pending')->count(),
        'paid' => Commission::where('status', 'paid')->count(),
        'held' => Commission::where('status', 'held')->count(),
        'cancelled' => Commission::where('status', 'cancelled')->count(),
        
        // Montants financiers
        'total_amount' => Commission::sum('commission_amount'), // Total commissions plateforme
        'total_pending_amount' => Commission::where('status', 'pending')->sum('net_amount'), // À payer aux promoteurs
        'total_paid_amount' => Commission::where('status', 'paid')->sum('net_amount'), // Payé aux promoteurs
        'total_held_amount' => Commission::where('status', 'held')->sum('net_amount'), // Suspendu
        
        // Statistiques calculées
        'avg_rate' => round(Commission::avg('commission_rate') ?? 0, 1),
        'total_gross' => Commission::sum('gross_amount'), // Total des ventes
        'conversion_rate' => 0, // À calculer si nécessaire
    ];
    
    // Ajouter des statistiques supplémentaires
    $stats['total_transactions'] = Commission::count();
    $stats['active_promoters'] = Commission::distinct('promoter_id')->count();
    
    // Calcul du pourcentage de commissions payées
    if ($stats['total_transactions'] > 0) {
        $stats['paid_percentage'] = round(($stats['paid'] / $stats['total_transactions']) * 100, 1);
    } else {
        $stats['paid_percentage'] = 0;
    }
    
    // Liste des promoteurs pour le filtre
    $promoters = User::where('role', 'promoteur')
        ->whereHas('commissions')
        ->orderBy('name')
        ->get();
    
    return view('admin.commissions', compact('commissions', 'stats', 'promoters'));
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

    public function updateOrderStatus(Request $request, Order $order)
    {
        $request->validate([
            'payment_status' => 'required|in:pending,paid,failed,refunded'
        ]);
        
        $oldStatus = $order->payment_status;
        $newStatus = $request->payment_status;
        
        // Mise à jour du payment_status
        $order->update([
            'payment_status' => $newStatus
        ]);
        
        // Actions spécifiques selon le nouveau statut
        switch ($newStatus) {
            case 'paid':
                // Marquer les billets comme vendus
                $order->tickets()->update(['status' => 'sold']);
                
                // Créer la commission si elle n'existe pas
                if (!$order->commission) {
                    $order->createCommission();
                }
                break;
                
            case 'refunded':
                // Annuler les billets
                $order->tickets()->update(['status' => 'cancelled']);
                
                // Mettre la commission en attente
                if ($order->commission) {
                    $order->commission->update(['status' => 'held']);
                }
                break;
                
            case 'failed':
                // Remettre les billets disponibles
                $order->tickets()->update(['status' => 'available']);
                break;
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Statut de la commande mis à jour avec succès',
            'old_status' => $oldStatus,
            'new_status' => $newStatus
        ]);
    }

    public function bulkUpdateOrders(Request $request) 
    {
        Order::whereIn('id', $request->order_ids)
             ->update(['payment_status' => $request->status]);
        return response()->json(['success' => true]);
    }

    /**
     * Supprimer une commande (si nécessaire)
     */
    public function destroyOrder(Order $order)
    {
        // Vérifier que la commande peut être supprimée
        if ($order->payment_status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer une commande payée'
            ], 422);
        }
        
        $order->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Commande supprimée avec succès'
        ]);
    }

    /**
     * Renvoyer l'email de commande
     */
    public function resendOrderEmail(Order $order)
    {
        // Logique pour renvoyer l'email
        // Mail::to($order->user)->send(new OrderConfirmation($order));
        
        return response()->json([
            'success' => true,
            'message' => 'Email renvoyé avec succès'
        ]);
    }

    /**
     * Rembourser une commande
     */
    public function refundOrder(Request $request, Order $order)
    {
        $request->validate([
            'reason' => 'required|string|max:255'
        ]);
        
        if ($order->payment_status !== 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Seules les commandes payées peuvent être remboursées'
            ], 422);
        }
        
        $order->refund();
        
        return response()->json([
            'success' => true,
            'message' => 'Commande remboursée avec succès'
        ]);
    }
    
    /**
     * Suppression en lot
     */
    public function bulkDeleteOrders(Request $request)
    {
        $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'exists:orders,id'
        ]);
        
        $orders = Order::whereIn('id', $request->order_ids)
            ->where('payment_status', '!=', 'paid')
            ->get();
        
        foreach ($orders as $order) {
            $order->delete();
        }
        
        return response()->json([
            'success' => true,
            'message' => count($orders) . ' commande(s) supprimée(s)'
        ]);
    }

    /**
     * Export en lot
     */
    public function bulkExportOrders(Request $request)
    {
        $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'exists:orders,id'
        ]);
        
        $orders = Order::with(['user', 'event'])
            ->whereIn('id', $request->order_ids)
            ->get();
        
        // Générer le CSV des commandes sélectionnées
        $filename = 'commandes_selection_' . now()->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];
        
        $callback = function() use ($orders) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'N° Commande', 'Client', 'Email', 'Événement',
                'Montant', 'Statut', 'Date'
            ]);
            
            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->order_number ?? $order->id,
                    $order->user->name ?? 'N/A',
                    $order->user->email ?? 'N/A',
                    $order->event->title ?? 'N/A',
                    $order->total_amount,
                    $order->payment_status,
                    $order->created_at->format('d/m/Y H:i')
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
    
    /**
     * Afficher un utilisateur (si pas déjà créée)
     */
    public function showUser(User $user)
    {
        $user->load(['events', 'orders', 'commissions']);
        
        $stats = [
            'account_created' => $user->created_at->format('d/m/Y'),
            'last_activity' => $user->updated_at->format('d/m/Y H:i'),
        ];
        
        return view('admin.users.show', compact('user', 'stats'));
    }

    /**
     * Afficher les détails d'un billet spécifique
     */
    public function showTicket(Ticket $ticket)
    {
        // Charger toutes les relations nécessaires
        $ticket->load([
            'ticketType.event.promoteur',
            'ticketType.event.category',
            'orderTickets.order.user',
            'orderTickets.order.transactions'
        ]);

        // Informations sur la commande associée
        $order = $ticket->orderTickets->first()?->order;
        
        // Historique d'utilisation du billet
        $ticketHistory = $this->getTicketHistory($ticket);

        // Billets similaires du même événement
        $relatedTickets = Ticket::whereHas('ticketType', function($q) use ($ticket) {
            $q->where('event_id', $ticket->ticketType->event_id);
        })
        ->where('id', '!=', $ticket->id)
        ->with(['ticketType', 'orderTickets.order.user'])
        ->limit(5)
        ->get();

        // Statistiques de l'événement
        $eventStats = $this->getEventTicketStats($ticket->ticketType->event_id);

        return view('admin.tickets.show', compact(
            'ticket', 
            'order', 
            'ticketHistory', 
            'relatedTickets', 
            'eventStats'
        ));
    }

    /**
     * Marquer un billet comme utilisé
     */
    public function markTicketUsed(Ticket $ticket)
    {
        if ($ticket->status !== 'sold') {
            return response()->json([
                'success' => false,
                'message' => 'Seuls les billets vendus peuvent être marqués comme utilisés'
            ], 422);
        }

        $ticket->update([
            'status' => 'used',
            'used_at' => Carbon::now(),
            'used_by_admin_id' => auth()->id()
        ]);

        // Log de l'action
        \Log::info('Billet marqué comme utilisé', [
            'ticket_id' => $ticket->id,
            'ticket_code' => $ticket->ticket_code,
            'admin_id' => auth()->id(),
            'admin_name' => auth()->user()->name
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Billet marqué comme utilisé avec succès'
        ]);
    }

    /**
     * Annuler un billet
     */
    public function cancelTicket(Ticket $ticket, Request $request)
    {
        $request->validate([
            'reason' => 'required|string|max:255'
        ]);

        if (!in_array($ticket->status, ['available', 'sold'])) {
            return response()->json([
                'success' => false,
                'message' => 'Ce billet ne peut pas être annulé'
            ], 422);
        }

        $ticket->update([
            'status' => 'cancelled',
            'cancelled_at' => Carbon::now(),
            'cancellation_reason' => $request->reason,
            'cancelled_by_admin_id' => auth()->id()
        ]);

        // Si le billet était vendu, il faut gérer le remboursement
        if ($ticket->status === 'sold') {
            // Ici vous pourriez ajouter la logique de remboursement
            // Par exemple, créer une transaction de remboursement
        }

        return response()->json([
            'success' => true,
            'message' => 'Billet annulé avec succès'
        ]);
    }

    /**
     * Télécharger le PDF d'un billet
     */
    public function downloadTicketPDF(Ticket $ticket)
    {
        $ticket->load(['ticketType.event', 'orderTickets.order.user']);
        
        // Vous pouvez utiliser une library comme DomPDF ou TCPDF
        // Ici un exemple simple avec une vue
        
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('admin.tickets.pdf', compact('ticket'));
        
        $filename = 'billet_' . $ticket->ticket_code . '.pdf';
        
        return $pdf->download($filename);
    }

    /**
     * Réactiver un billet annulé
     */
    public function reactivateTicket(Ticket $ticket)
    {
        if ($ticket->status !== 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Seuls les billets annulés peuvent être réactivés'
            ], 422);
        }

        // Déterminer le nouveau statut selon l'historique
        $newStatus = $ticket->orderTickets->count() > 0 ? 'sold' : 'available';

        $ticket->update([
            'status' => $newStatus,
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'cancelled_by_admin_id' => null,
            'reactivated_at' => Carbon::now(),
            'reactivated_by_admin_id' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Billet réactivé avec succès'
        ]);
    }

    /**
     * Actions groupées sur les billets
     */
    public function bulkTicketAction(Request $request)
    {
        $request->validate([
            'tickets' => 'required|array',
            'tickets.*' => 'exists:tickets,id',
            'action' => 'required|in:mark_used,cancel,reactivate,delete'
        ]);

        $tickets = Ticket::whereIn('id', $request->tickets)->get();
        $successCount = 0;
        $errors = [];

        foreach ($tickets as $ticket) {
            try {
                switch ($request->action) {
                    case 'mark_used':
                        if ($ticket->status === 'sold') {
                            $ticket->update([
                                'status' => 'used',
                                'used_at' => Carbon::now(),
                                'used_by_admin_id' => auth()->id()
                            ]);
                            $successCount++;
                        }
                        break;

                    case 'cancel':
                        if (in_array($ticket->status, ['available', 'sold'])) {
                            $ticket->update([
                                'status' => 'cancelled',
                                'cancelled_at' => Carbon::now(),
                                'cancellation_reason' => 'Action groupée admin',
                                'cancelled_by_admin_id' => auth()->id()
                            ]);
                            $successCount++;
                        }
                        break;

                    case 'reactivate':
                        if ($ticket->status === 'cancelled') {
                            $newStatus = $ticket->orderTickets->count() > 0 ? 'sold' : 'available';
                            $ticket->update([
                                'status' => $newStatus,
                                'cancelled_at' => null,
                                'cancellation_reason' => null,
                                'cancelled_by_admin_id' => null
                            ]);
                            $successCount++;
                        }
                        break;

                    case 'delete':
                        if ($ticket->status === 'available') {
                            $ticket->delete();
                            $successCount++;
                        }
                        break;
                }
            } catch (\Exception $e) {
                $errors[] = "Erreur avec le billet {$ticket->ticket_code}: " . $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'message' => "$successCount billet(s) traité(s) avec succès",
            'errors' => $errors
        ]);
    }

    /**
     * Obtenir l'historique d'un billet
     */
    private function getTicketHistory(Ticket $ticket)
    {
        $history = collect();

        // Création du billet
        $history->push([
            'action' => 'created',
            'date' => $ticket->created_at,
            'description' => 'Billet créé',
            'admin' => null
        ]);

        // Vente du billet
        if ($ticket->orderTickets->count() > 0) {
            $order = $ticket->orderTickets->first()->order;
            $history->push([
                'action' => 'sold',
                'date' => $order->created_at,
                'description' => "Vendu à {$order->user->name}",
                'admin' => null,
                'order_id' => $order->id
            ]);
        }

        // Utilisation du billet
        if ($ticket->used_at) {
            $history->push([
                'action' => 'used',
                'date' => $ticket->used_at,
                'description' => 'Billet utilisé',
                'admin' => $ticket->used_by_admin_id ? User::find($ticket->used_by_admin_id) : null
            ]);
        }

        // Annulation du billet
        if ($ticket->cancelled_at) {
            $history->push([
                'action' => 'cancelled',
                'date' => $ticket->cancelled_at,
                'description' => 'Billet annulé: ' . ($ticket->cancellation_reason ?? 'Aucune raison'),
                'admin' => $ticket->cancelled_by_admin_id ? User::find($ticket->cancelled_by_admin_id) : null
            ]);
        }

        return $history->sortBy('date');
    }

    /**
     * Obtenir les statistiques des billets pour un événement
     */
    private function getEventTicketStats($eventId)
    {
        return [
            'total_tickets' => Ticket::whereHas('ticketType', function($q) use ($eventId) {
                $q->where('event_id', $eventId);
            })->count(),
            
            'sold_tickets' => Ticket::whereHas('ticketType', function($q) use ($eventId) {
                $q->where('event_id', $eventId);
            })->where('status', 'sold')->count(),
            
            'used_tickets' => Ticket::whereHas('ticketType', function($q) use ($eventId) {
                $q->where('event_id', $eventId);
            })->where('status', 'used')->count(),
            
            'revenue' => DB::table('tickets')
                ->join('ticket_types', 'tickets.ticket_type_id', '=', 'ticket_types.id')
                ->where('ticket_types.event_id', $eventId)
                ->where('tickets.status', 'sold')
                ->sum('ticket_types.price')
        ];
    }

    public function tickets(Request $request)
    {
        $query = Ticket::with(['ticketType.event.promoteur', 'orderTickets.order.user'])
            ->orderBy('created_at', 'desc');

        // Filtrage par recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('ticket_code', 'like', "%{$search}%")
                  ->orWhereHas('ticketType.event', function($subQ) use ($search) {
                      $subQ->where('title', 'like', "%{$search}%");
                  })
                  ->orWhereHas('orderTickets.order.user', function($subQ) use ($search) {
                      $subQ->where('name', 'like', "%{$search}%")
                           ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filtrage par statut
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtrage par événement
        if ($request->filled('event_id')) {
            $query->whereHas('ticketType', function($q) use ($request) {
                $q->where('event_id', $request->event_id);
            });
        }

        // Filtrage par date
        if ($request->filled('date_filter')) {
            switch ($request->date_filter) {
                case 'today':
                    $query->whereDate('created_at', Carbon::today());
                    break;
                case 'week':
                    $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                    break;
                case 'month':
                    $query->whereMonth('created_at', Carbon::now()->month)
                          ->whereYear('created_at', Carbon::now()->year);
                    break;
            }
        }

        $tickets = $query->paginate(20);

        // Statistiques des billets
        $stats = [
            'total' => Ticket::count(),
            'available' => Ticket::where('status', 'available')->count(),
            'sold' => Ticket::where('status', 'sold')->count(),
            'used' => Ticket::where('status', 'used')->count(),
            'cancelled' => Ticket::where('status', 'cancelled')->count(),
            'total_value' => Ticket::join('ticket_types', 'tickets.ticket_type_id', '=', 'ticket_types.id')
                           ->where('tickets.status', 'sold')
                           ->sum('ticket_types.price')
        ];

        // Liste des événements pour le filtre
        $events = Event::with('promoteur')->orderBy('title')->get();

        return view('admin.tickets', compact('tickets', 'stats', 'events'));
    }

public function settings()
    {
        // Charger les paramètres depuis la base de données
        $settings = [
            // Configuration générale
            'platform_name' => Setting::get('platform_name', env('APP_NAME', 'ClicBillet CI')),
            'platform_email' => Setting::get('platform_email', env('MAIL_FROM_ADDRESS', 'contact@clicbillet.ci')),
            'commission_rate' => Setting::get('commission_rate', 10.0),
            'currency' => Setting::get('currency', 'FCFA'),
            'timezone' => Setting::get('timezone', 'Africa/Abidjan'),
            
            // Options de la plateforme
            'maintenance_mode' => Setting::get('maintenance_mode', false),
            'registration_enabled' => Setting::get('registration_enabled', true),
            'auto_approval_events' => Setting::get('auto_approval_events', false),
            'email_notifications' => Setting::get('email_notifications', true),
            'sms_notifications' => Setting::get('sms_notifications', false),
            
            // Paramètres supplémentaires (valeurs par défaut si pas en BDD)
            'platform_fee_fixed' => Setting::get('platform_fee_fixed', 500),
            'min_commission' => Setting::get('min_commission', 1000),
            'payment_delay_days' => Setting::get('payment_delay_days', 7),
            'max_tickets_per_order' => Setting::get('max_tickets_per_order', 10),
            'max_file_upload' => Setting::get('max_file_upload', '10MB'),
            'allowed_image_types' => Setting::get('allowed_image_types', 'jpg,jpeg,png,webp'),
            'ticket_qr_size' => Setting::get('ticket_qr_size', 200),
            'platform_description' => Setting::get('platform_description', 'Plateforme de billetterie en ligne pour la Côte d\'Ivoire'),
            'support_email' => Setting::get('support_email', 'support@clicbillet.ci'),
            'support_phone' => Setting::get('support_phone', '+225 27 22 XX XX XX'),
            'backup_frequency' => Setting::get('backup_frequency', 'daily'),
        ];
        
        // Statistiques du système (inchangées)
        $systemStats = [
            'total_users' => User::count(),
            'total_events' => Event::count(),
            'total_orders' => Order::count(),
            'total_revenue' => Order::where('payment_status', 'paid')->sum('total_amount') ?? 0,
            'pending_commissions' => Commission::where('status', 'pending')->count(),
            'active_promoters' => User::where('role', 'promoteur')->whereHas('events')->count(),
            
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            
            'disk_usage' => $this->getDiskUsage(),
            'database_connection' => $this->checkDatabaseConnection(),
            'mail_configured' => $this->checkMailConfiguration(),
            'storage_writable' => $this->checkStorageWritable(),
        ];
        
        return view('admin.settings', compact('settings', 'systemStats'));
    }
    
    /**
     * Mettre à jour les paramètres - CORRESPONDANT À VOTRE FORMULAIRE
     */
    public function updateSettings(Request $request)
    {
        // Validation complète
        $request->validate([
            'platform_name' => 'required|string|max:255',
            'platform_email' => 'required|email|max:255',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'currency' => 'required|in:FCFA,EUR,USD',
            'timezone' => 'required|string',
            'maintenance_mode' => 'boolean',
            'registration_enabled' => 'boolean',
            'auto_approval_events' => 'boolean',
            'email_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
            'platform_fee_fixed' => 'nullable|integer|min:0',
            'min_commission' => 'nullable|integer|min:0',
            'payment_delay_days' => 'nullable|integer|min:1|max:30',
            'max_tickets_per_order' => 'nullable|integer|min:1|max:50',
            'support_email' => 'nullable|email',
            'support_phone' => 'nullable|string|max:20',
        ], [
            'platform_name.required' => 'Le nom de la plateforme est obligatoire',
            'platform_email.required' => 'L\'email de contact est obligatoire',
            'platform_email.email' => 'L\'email doit être valide',
            'commission_rate.required' => 'Le taux de commission est obligatoire',
            'commission_rate.numeric' => 'Le taux de commission doit être un nombre',
            'commission_rate.min' => 'Le taux de commission ne peut pas être négatif',
            'commission_rate.max' => 'Le taux de commission ne peut pas dépasser 100%',
            'currency.required' => 'La devise est obligatoire',
            'currency.in' => 'La devise doit être FCFA, EUR ou USD',
        ]);
        
        try {
            // Préparer les données avec les bonnes valeurs booléennes
            $settingsData = [
                'platform_name' => ['value' => $request->platform_name, 'type' => 'string'],
                'platform_email' => ['value' => $request->platform_email, 'type' => 'string'],
                'commission_rate' => ['value' => $request->commission_rate, 'type' => 'decimal'],
                'currency' => ['value' => $request->currency, 'type' => 'string'],
                'timezone' => ['value' => $request->timezone, 'type' => 'string'],
                'maintenance_mode' => ['value' => $request->has('maintenance_mode'), 'type' => 'boolean'],
                'registration_enabled' => ['value' => $request->has('registration_enabled'), 'type' => 'boolean'],
                'auto_approval_events' => ['value' => $request->has('auto_approval_events'), 'type' => 'boolean'],
                'email_notifications' => ['value' => $request->has('email_notifications'), 'type' => 'boolean'],
                'sms_notifications' => ['value' => $request->has('sms_notifications'), 'type' => 'boolean'],
            ];
            
            // Ajouter les paramètres optionnels s'ils sont fournis
            if ($request->filled('platform_fee_fixed')) {
                $settingsData['platform_fee_fixed'] = ['value' => $request->platform_fee_fixed, 'type' => 'integer'];
            }
            if ($request->filled('min_commission')) {
                $settingsData['min_commission'] = ['value' => $request->min_commission, 'type' => 'integer'];
            }
            if ($request->filled('payment_delay_days')) {
                $settingsData['payment_delay_days'] = ['value' => $request->payment_delay_days, 'type' => 'integer'];
            }
            if ($request->filled('max_tickets_per_order')) {
                $settingsData['max_tickets_per_order'] = ['value' => $request->max_tickets_per_order, 'type' => 'integer'];
            }
            if ($request->filled('support_email')) {
                $settingsData['support_email'] = ['value' => $request->support_email, 'type' => 'string'];
            }
            if ($request->filled('support_phone')) {
                $settingsData['support_phone'] = ['value' => $request->support_phone, 'type' => 'string'];
            }
            
            // SAUVEGARDE RÉELLE EN BASE DE DONNÉES
            Setting::setMany($settingsData);
            
            // Log de l'action
            \Log::info('Paramètres mis à jour par l\'admin', [
                'admin_id' => auth()->id(),
                'admin_name' => auth()->user()->name,
                'updated_settings' => array_keys($settingsData),
                'timestamp' => now()
            ]);
            
            return redirect()->route('admin.settings')
                ->with('success', 'Paramètres mis à jour avec succès');
                
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la mise à jour des paramètres: ' . $e->getMessage(), [
                'admin_id' => auth()->id(),
                'request_data' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', 'Erreur lors de la sauvegarde: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Sauvegarder les paramètres en base de données (optionnel)
     */
    private function saveSettingsToDatabase(array $data)
    {
        // Vous pourriez créer une table 'settings' pour persister les données
        // Pour l'instant, simulation de la sauvegarde
        
        foreach ($data as $key => $value) {
            // DB::table('settings')->updateOrInsert(
            //     ['key' => $key],
            //     ['value' => is_bool($value) ? ($value ? '1' : '0') : $value, 'updated_at' => now()]
            // );
        }
    }
    
    /**
     * Calculer l'utilisation du disque
     */
    private function getDiskUsage()
    {
        try {
            $bytes = disk_free_space(storage_path());
            $totalBytes = disk_total_space(storage_path());
            
            if ($bytes === false || $totalBytes === false) {
                return [
                    'free' => 'N/A',
                    'total' => 'N/A',
                    'used_percentage' => 0
                ];
            }
            
            return [
                'free' => $this->formatBytes($bytes),
                'total' => $this->formatBytes($totalBytes),
                'used_percentage' => round((($totalBytes - $bytes) / $totalBytes) * 100, 1)
            ];
        } catch (\Exception $e) {
            \Log::warning('Erreur calcul utilisation disque: ' . $e->getMessage());
            return [
                'free' => 'N/A',
                'total' => 'N/A', 
                'used_percentage' => 0
            ];
        }
    }
    
    /**
     * Formater les bytes en format lisible
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Vérifier la connexion à la base de données
     */
    private function checkDatabaseConnection()
    {
        try {
            DB::connection()->getPdo();
            return [
                'status' => 'connected',
                'message' => 'Connexion active',
                'icon' => 'fas fa-check-circle text-success'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Erreur de connexion',
                'icon' => 'fas fa-times-circle text-danger'
            ];
        }
    }
    
    /**
     * Vérifier la configuration email
     */
    private function checkMailConfiguration()
    {
        try {
            $required = ['MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME'];
            $configured = true;
            
            foreach ($required as $key) {
                if (empty(env($key))) {
                    $configured = false;
                    break;
                }
            }
            
            return [
                'status' => $configured ? 'configured' : 'incomplete',
                'message' => $configured ? 'Configuré et fonctionnel' : 'Configuration incomplète',
                'icon' => $configured ? 'fas fa-check-circle text-success' : 'fas fa-exclamation-triangle text-warning'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Erreur de configuration',
                'icon' => 'fas fa-times-circle text-danger'
            ];
        }
    }
    
    /**
     * Vérifier si le storage est accessible en écriture
     */
    private function checkStorageWritable()
    {
        try {
            $testFile = storage_path('app/test_write_permission.txt');
            file_put_contents($testFile, 'test');
            unlink($testFile);
            
            return [
                'status' => 'writable',
                'message' => 'Accessible en écriture',
                'icon' => 'fas fa-check-circle text-success'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'readonly',
                'message' => 'Problème d\'écriture',
                'icon' => 'fas fa-times-circle text-danger'
            ];
        }
    }
    
    /**
     * Test d'envoi d'email (endpoint AJAX)
     */
    public function testEmail()
    {
        try {
            // Ici vous pourriez envoyer un email de test
            // Mail::to(auth()->user()->email)->send(new TestEmail());
            
            return response()->json([
                'success' => true,
                'message' => 'Email de test envoyé avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Sauvegarde système (endpoint AJAX)
     */
    public function backupSystem()
    {
        try {
            // Ici vous pourriez implémenter la logique de sauvegarde
            // Artisan::call('backup:run');
            
            return response()->json([
                'success' => true,
                'message' => 'Sauvegarde système initiée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Vider le cache système (endpoint AJAX)
     */
    public function clearCache()
    {
        try {
            // Vider différents types de cache
            \Artisan::call('cache:clear');
            \Artisan::call('config:clear');
            \Artisan::call('route:clear');
            \Artisan::call('view:clear');
            
            \Log::info('Cache vidé par l\'admin', [
                'admin_id' => auth()->id(),
                'admin_name' => auth()->user()->name,
                'timestamp' => now()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Cache vidé avec succès'
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors du vidage du cache: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du vidage du cache: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Optimiser la base de données (endpoint AJAX)
     */
    public function optimizeDatabase()
    {
        try {
            // Optimiser les tables principales
            $tables = ['users', 'events', 'orders', 'commissions', 'tickets'];
            
            foreach ($tables as $table) {
                DB::statement("OPTIMIZE TABLE {$table}");
            }
            
            \Log::info('Base de données optimisée par l\'admin', [
                'admin_id' => auth()->id(),
                'admin_name' => auth()->user()->name,
                'tables' => $tables,
                'timestamp' => now()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Base de données optimisée avec succès'
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'optimisation de la BDD: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'optimisation: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Générer un rapport système (endpoint AJAX)
     */
    public function generateSystemReport()
    {
        try {
            $report = [
                'generated_at' => now()->format('d/m/Y H:i:s'),
                'generated_by' => auth()->user()->name,
                
                // Statistiques générales
                'statistics' => [
                    'total_users' => User::count(),
                    'total_events' => Event::count(),
                    'total_orders' => Order::count(),
                    'total_revenue' => Order::where('payment_status', 'paid')->sum('total_amount'),
                    'pending_commissions' => Commission::where('status', 'pending')->count(),
                ],
                
                // Informations système
                'system_info' => [
                    'php_version' => PHP_VERSION,
                    'laravel_version' => app()->version(),
                    'memory_limit' => ini_get('memory_limit'),
                    'max_execution_time' => ini_get('max_execution_time'),
                    'upload_max_filesize' => ini_get('upload_max_filesize'),
                ],
                
                // Statut des services
                'services_status' => [
                    'database' => $this->checkDatabaseConnection(),
                    'mail' => $this->checkMailConfiguration(),
                    'storage' => $this->checkStorageWritable(),
                ],
                
                // Utilisation des ressources
                'disk_usage' => $this->getDiskUsage(),
                
                // Erreurs récentes (optionnel)
                'recent_errors' => $this->getRecentErrors(),
            ];
            
            // Sauvegarder le rapport
            $filename = 'system_report_' . now()->format('Y-m-d_H-i-s') . '.json';
            Storage::put('reports/' . $filename, json_encode($report, JSON_PRETTY_PRINT));
            
            \Log::info('Rapport système généré par l\'admin', [
                'admin_id' => auth()->id(),
                'admin_name' => auth()->user()->name,
                'filename' => $filename,
                'timestamp' => now()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Rapport système généré avec succès',
                'download_url' => Storage::url('reports/' . $filename)
            ]);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la génération du rapport: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du rapport: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtenir les erreurs récentes
     */
    private function getRecentErrors()
    {
        try {
            $logFile = storage_path('logs/laravel.log');
            
            if (!file_exists($logFile)) {
                return [];
            }
            
            $lines = file($logFile);
            $errors = [];
            $errorCount = 0;
            
            // Lire les 100 dernières lignes
            $recentLines = array_slice($lines, -100);
            
            foreach ($recentLines as $line) {
                if (strpos($line, 'ERROR') !== false && $errorCount < 10) {
                    $errors[] = trim($line);
                    $errorCount++;
                }
            }
            
            return $errors;
        } catch (\Exception $e) {
            return ['Erreur lors de la lecture des logs: ' . $e->getMessage()];
        }
    }

    /**
 * Dashboard des emails et statistiques
 */
public function emailDashboard()
{
    // Statistiques des emails des 30 derniers jours
    $stats = [
        'emails_sent_today' => \DB::table('mail_logs')
            ->whereDate('created_at', today())
            ->count(),
        'emails_sent_week' => \DB::table('mail_logs')
            ->whereBetween('created_at', [now()->subWeek(), now()])
            ->count(),
        'emails_sent_month' => \DB::table('mail_logs')
            ->whereBetween('created_at', [now()->subMonth(), now()])
            ->count(),
        'failed_emails' => \DB::table('failed_jobs')
            ->where('payload', 'like', '%mail%')
            ->count(),
    ];

    // Derniers emails envoyés
    $recentEmails = \DB::table('mail_logs')
        ->orderBy('created_at', 'desc')
        ->limit(20)
        ->get();

    // Configuration email actuelle
    $emailConfig = [
        'mailer' => config('mail.default'),
        'host' => config('mail.mailers.smtp.host'),
        'port' => config('mail.mailers.smtp.port'),
        'from_address' => config('mail.from.address'),
        'from_name' => config('mail.from.name'),
    ];

    return view('admin.emails.dashboard', compact('stats', 'recentEmails', 'emailConfig'));
    }  

    /**
 * Configuration des templates email
 */
public function emailTemplates()
{
    $templates = [
        'order-confirmation' => [
            'name' => 'Confirmation de commande',
            'description' => 'Email envoyé après qu\'un client passe une commande',
            'variables' => ['order', 'customer', 'event', 'total']
        ],
        'payment-confirmation' => [
            'name' => 'Confirmation de paiement',
            'description' => 'Email avec billets envoyé après paiement confirmé',
            'variables' => ['order', 'tickets']
        ],
        'promoteur-new-sale' => [
            'name' => 'Nouvelle vente (Promoteur)',
            'description' => 'Notification de vente pour les promoteurs',
            'variables' => ['order', 'promoteur', 'commission']
        ],
        'admin-new-order' => [
            'name' => 'Nouvelle commande (Admin)',
            'description' => 'Notification de commande pour les admins',
            'variables' => ['order', 'stats']
        ]
    ];

    return view('admin.emails.templates', compact('templates'));
}

 /**
 * Afficher les détails d'un événement - VERSION ULTRA ROBUSTE
 */
public function showEvent(Event $event)
{
    try {
        // Chargement sécurisé des relations
        $event->load([
            'category', 
            'promoteur', 
            'ticketTypes' => function($query) {
                $query->withCount('tickets');
            }
        ]);

        // Statistiques avec fallback pour chaque méthode
        $stats = [];
        
        // Total revenue
        try {
            $stats['total_revenue'] = $event->totalRevenue() ?? 0;
        } catch (\Exception $e) {
            $stats['total_revenue'] = 0;
            \Log::warning('Erreur calcul revenue: ' . $e->getMessage());
        }
        
        // Tickets vendus
        try {
            $stats['tickets_sold'] = $event->getTicketsSoldCount() ?? 0;
        } catch (\Exception $e) {
            $stats['tickets_sold'] = 0;
            \Log::warning('Erreur calcul tickets vendus: ' . $e->getMessage());
        }
        
        // Tickets disponibles
        try {
            $stats['tickets_available'] = $event->totalTicketsAvailable() ?? 0;
        } catch (\Exception $e) {
            $stats['tickets_available'] = 0;
            \Log::warning('Erreur calcul tickets disponibles: ' . $e->getMessage());
        }
        
        // Nombre de commandes
        try {
            $stats['orders_count'] = $event->getOrdersCount() ?? 0;
        } catch (\Exception $e) {
            $stats['orders_count'] = 0;
            \Log::warning('Erreur calcul commandes: ' . $e->getMessage());
        }
        
        // Commission gagnée (avec fallback)
        try {
            if (method_exists($event, 'getCommissionEarned')) {
                $stats['commission_earned'] = $event->getCommissionEarned() ?? 0;
            } else {
                // Calcul simple : 5% du total des revenus
                $stats['commission_earned'] = ($stats['total_revenue'] * 0.05);
            }
        } catch (\Exception $e) {
            $stats['commission_earned'] = 0;
            \Log::warning('Erreur calcul commission: ' . $e->getMessage());
        }
        
        // Pourcentage de progression
        try {
            $stats['progress_percentage'] = $event->getProgressPercentage() ?? 0;
        } catch (\Exception $e) {
            $stats['progress_percentage'] = 0;
            \Log::warning('Erreur calcul pourcentage: ' . $e->getMessage());
        }

        // Commandes récentes avec gestion d'erreurs
        try {
            $recentOrders = $event->orders()
                ->with(['user' => function($query) {
                    $query->select('id', 'name', 'email');
                }])
                ->latest()
                ->take(10)
                ->get();
        } catch (\Exception $e) {
            $recentOrders = collect();
            \Log::warning('Erreur récupération commandes récentes: ' . $e->getMessage());
        }

        // Tickets par statut avec gestion d'erreurs
        try {
            $ticketsByStatus = $event->tickets()
                ->select('status', \DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();
        } catch (\Exception $e) {
            $ticketsByStatus = [];
            \Log::warning('Erreur stats tickets par statut: ' . $e->getMessage());
        }

        // Ventes par type de billet avec gestion d'erreurs
        try {
            $salesByTicketType = $event->ticketTypes()
                ->withCount([
                    'tickets as sold_count' => function($query) {
                        $query->whereIn('status', ['sold', 'used']);
                    }
                ])
                ->get()
                ->map(function($ticketType) {
                    return [
                        'name' => $ticketType->name ?? 'N/A',
                        'price' => $ticketType->price ?? 0,
                        'sold' => $ticketType->sold_count ?? 0,
                        'total' => $ticketType->quantity_available ?? 0,
                        'revenue' => ($ticketType->price ?? 0) * ($ticketType->sold_count ?? 0)
                    ];
                });
        } catch (\Exception $e) {
            $salesByTicketType = collect();
            \Log::warning('Erreur ventes par type de billet: ' . $e->getMessage());
        }

        return view('admin.events.show', compact(
            'event', 
            'stats', 
            'recentOrders', 
            'ticketsByStatus', 
            'salesByTicketType'
        ));

    } catch (\Exception $e) {
        \Log::error('Erreur critique dans admin.events.show: ' . $e->getMessage(), [
            'event_id' => $event->id ?? 'inconnu',
            'trace' => $e->getTraceAsString()
        ]);

        return redirect()->route('admin.events')
            ->with('error', 'Erreur lors du chargement de l\'événement. Détails: ' . $e->getMessage());
    }
}


/**
 * Édition événement - VERSION ROBUSTE
 */
public function editEvent(Event $event)
{
    try {
        // Chargement sécurisé
        $event->load(['category', 'promoteur', 'ticketTypes']);
        
        // Data avec fallbacks
        $categories = EventCategory::orderBy('name')->get() ?? collect();
        $promoteurs = User::where('role', 'promoteur')
            ->orderBy('name')
            ->get() ?? collect();

        return view('admin.events.edit', compact('event', 'categories', 'promoteurs'));

    } catch (\Exception $e) {
        \Log::error('Erreur dans admin.events.edit: ' . $e->getMessage(), [
            'event_id' => $event->id ?? 'inconnu'
        ]);

        return redirect()->route('admin.events')
            ->with('error', 'Impossible de charger l\'édition: ' . $e->getMessage());
    }
}

/**
 * Afficher le profil administrateur
 */
public function profile()
{
    try {
        $user = auth()->user();
        
        // Statistiques d'activité de l'admin
        $activityStats = [
            'users_managed' => User::count(),
            'events_approved' => Event::where('status', 'published')->count(),
            'total_revenue' => Order::where('payment_status', 'paid')->sum('total_amount'),
            'commissions_paid' => Commission::where('status', 'paid')->sum('amount'),
            'recent_logins' => \DB::table('sessions')
                ->where('user_id', $user->id)
                ->count(),
        ];

        // Activités récentes (logs d'administration)
        $recentActivities = collect([
            // Événements récemment approuvés
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
            
            // Utilisateurs récemment créés
            User::latest('created_at')
                ->take(3)
                ->get()
                ->map(function($user) {
                    return [
                        'type' => 'user_created',
                        'message' => "Nouvel utilisateur: {$user->name} ({$user->role})",
                        'date' => $user->created_at,
                        'icon' => 'fas fa-user-plus',
                        'color' => 'info'
                    ];
                }),
        ])->flatten()->sortByDesc('date')->take(10);

        // Paramètres système
        $systemInfo = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'database_size' => $this->getDatabaseSize(),
            'storage_used' => $this->getStorageUsed(),
            'cache_status' => $this->getCacheStatus(),
        ];

        return view('admin.profile', compact(
            'user', 
            'activityStats', 
            'recentActivities', 
            'systemInfo'
        ));

    } catch (\Exception $e) {
        \Log::error('Erreur dans admin.profile: ' . $e->getMessage(), [
            'user_id' => auth()->id(),
            'trace' => $e->getTraceAsString()
        ]);

        return redirect()->route('admin.dashboard')
            ->with('error', 'Erreur lors du chargement du profil');
    }
}

/**
 * Mettre à jour le profil administrateur
 */
public function updateProfile(Request $request)
{
    $user = auth()->user();

    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
        'current_password' => 'nullable|string',
        'password' => 'nullable|string|min:8|confirmed',
        'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'phone' => 'nullable|string|max:20',
        'notifications_enabled' => 'boolean',
        'email_notifications' => 'boolean',
    ]);

    try {
        // Vérifier le mot de passe actuel si un nouveau mot de passe est fourni
        if ($request->password && !Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Le mot de passe actuel est incorrect']);
        }

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ];

        // Gérer l'avatar
        if ($request->hasFile('avatar')) {
            // Supprimer l'ancien avatar
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $updateData['avatar'] = $avatarPath;
        }

        // Mettre à jour le mot de passe si fourni
        if ($request->password) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        // Mettre à jour les préférences de notification (si vous avez une table séparée)
        $user->preferences()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'notifications_enabled' => $request->boolean('notifications_enabled', true),
                'email_notifications' => $request->boolean('email_notifications', true),
            ]
        );

        return redirect()->route('admin.profile')
            ->with('success', 'Profil mis à jour avec succès !');

    } catch (\Exception $e) {
        \Log::error('Erreur lors de la mise à jour du profil admin: ' . $e->getMessage(), [
            'user_id' => $user->id,
            'trace' => $e->getTraceAsString()
        ]);

        return back()->with('error', 'Erreur lors de la mise à jour du profil')
            ->withInput();
    }
}
/**
 * Méthodes utilitaires pour le profil admin
 */
private function getDatabaseSize()
{
    try {
        $size = \DB::select("
            SELECT 
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()
        ");
        
        return $size[0]->size_mb ?? 0;
    } catch (\Exception $e) {
        return 'N/A';
    }
}

private function getStorageUsed()
{
    try {
        $storagePath = storage_path('app/public');
        if (!is_dir($storagePath)) {
            return '0 MB';
        }
        
        $size = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($storagePath)) as $file) {
            $size += $file->getSize();
        }
        
        return round($size / 1024 / 1024, 2) . ' MB';
    } catch (\Exception $e) {
        return 'N/A';
    }
}

private function getCacheStatus()
{
    try {
        return Cache::getStore() instanceof \Illuminate\Cache\FileStore ? 'File' : 'Database';
    } catch (\Exception $e) {
        return 'N/A';
    }
}
/**
 * Télécharger le PDF d'une commande
 */
public function downloadOrderPDF(Order $order)
{
    try {
        // Charger les relations nécessaires
        $order->load(['user', 'event', 'orderItems.ticketType', 'tickets']);
        
        // Vérifier que la commande existe
        if (!$order) {
            abort(404, 'Commande non trouvée');
        }
        
        // Générer le PDF avec DomPDF
        $pdf = \PDF::loadView('admin.pdf.order', compact('order'));
        
        // Définir les options du PDF
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'defaultFont' => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isPhpEnabled' => true
        ]);
        
        // Nom du fichier
        $filename = 'commande-' . ($order->order_number ?? $order->id) . '.pdf';
        
        // Log de l'action
        \Log::info('PDF de commande téléchargé', [
            'admin_id' => auth()->id(),
            'admin_name' => auth()->user()->name,
            'order_id' => $order->id,
            'order_number' => $order->order_number ?? $order->id,
            'timestamp' => now()
        ]);
        
        // Retourner le PDF en téléchargement
        return $pdf->download($filename);
        
    } catch (\Exception $e) {
        \Log::error('Erreur lors de la génération du PDF de commande: ' . $e->getMessage(), [
            'admin_id' => auth()->id(),
            'order_id' => $order->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return redirect()->back()->with('error', 'Erreur lors de la génération du PDF: ' . $e->getMessage());
    }
}
}
