<?php

namespace App\Http\Controllers\Promoteur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\Commission;

class SalesController extends Controller
{
    public function index(Request $request)
    {
        $promoteurId = Auth::id();
        $period = $request->get('period', 'all');
        
        try {
            // Définir la période
            $startDate = $this->getStartDate($period);
            $endDate = now();
            
            // Gérer les dates personnalisées depuis le formulaire de filtres
            if ($request->filled('start_date')) {
                $startDate = Carbon::parse($request->start_date)->startOfDay();
            }
            if ($request->filled('end_date')) {
                $endDate = Carbon::parse($request->end_date)->endOfDay();
            }
            
            // Formatter les dates pour la vue
            $dateFormatted = $this->formatDatesForView($startDate, $endDate, $period);
            $startDate = $dateFormatted['startDate'];
            $endDate = $dateFormatted['endDate'];
            
            // Statistiques générales (pour $stats)
            $stats = $this->getSalesStats($promoteurId, $startDate, $endDate);
            
            // CORRECTION MAJEURE: Créer $totalStats pour les cartes de statistiques
            $totalStats = [
                'total_revenue' => $stats['total_revenue'],
                'total_tickets' => $stats['total_tickets_sold'], 
                'total_orders' => $stats['total_orders'],
                'average_order' => $stats['average_order_value']
            ];
            
            // Commandes avec les bonnes relations
            $orders = $this->getOrders($promoteurId, $startDate, $endDate);
            
            // Événements avec données de ventes
            $eventsWithSales = $this->getEventsWithSales($promoteurId, $startDate, $endDate);
            
            // Ventes par jour pour le graphique
            $dailySales = $this->getDailySales($promoteurId, $startDate, $endDate);
            
            // Ventes par événement
            $salesByEvent = $this->getSalesByEvent($promoteurId, $startDate, $endDate);
            
            return view('promoteur.sales.index', compact(
                'orders', 
                'stats', 
                'totalStats',  // AJOUTÉ pour les cartes de stats
                'period',
                'startDate',   // AJOUTÉ pour le formulaire de filtres
                'endDate',     // AJOUTÉ pour le formulaire de filtres
                'eventsWithSales',
                'dailySales',
                'salesByEvent'
            ));
            
        } catch (\Exception $e) {
            Log::error('Erreur dans SalesController@index: ' . $e->getMessage(), [
                'promoteur_id' => $promoteurId,
                'period' => $period,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Données par défaut en cas d'erreur
            $stats = $this->getDefaultStats();
            $totalStats = [
                'total_revenue' => 0,
                'total_tickets' => 0,
                'total_orders' => 0,
                'average_order' => 0
            ];
            
            // Dates par défaut
            $dateFormatted = $this->formatDatesForView(null, null, $period);
            
            return view('promoteur.sales.index', [
                'orders' => collect(),
                'stats' => $stats,
                'totalStats' => $totalStats,  // AJOUTÉ
                'period' => $period,
                'startDate' => $dateFormatted['startDate'],    // AJOUTÉ
                'endDate' => $dateFormatted['endDate'],        // AJOUTÉ
                'eventsWithSales' => collect(),
                'dailySales' => collect(),
                'salesByEvent' => collect()
            ])->with('error', 'Erreur lors du chargement des données de vente');
        }
    }

    /**
     * Obtenir les statistiques de ventes - CORRECTIONS SQL AMBIGUÏTÉ
     */
    private function getSalesStats($promoteurId, $startDate, $endDate)
    {
        // UTILISER EXACTEMENT LES MÊMES REQUÊTES QUE DANS PromoteurController::dashboard()
        
        // Revenus totaux (même requête que dashboard)
        $totalRevenue = Order::whereHas('orderItems.ticketType.event', function($query) use ($promoteurId) {
                $query->where('promoter_id', $promoteurId);
            })
            ->where('payment_status', 'paid')
            ->when($startDate, function($query) use ($startDate, $endDate) {
                $query->whereBetween('orders.created_at', [$startDate, $endDate]); // PRÉCISER LA TABLE
            })
            ->sum('total_amount');

        // Billets vendus (même requête que dashboard) - CORRECTION AMBIGUÏTÉ
        $totalTicketsSold = Ticket::whereHas('ticketType.event', function($query) use ($promoteurId) {
                $query->where('promoter_id', $promoteurId);
            })
            ->where('tickets.status', '!=', 'available') // PRÉCISER LA TABLE
            ->when($startDate, function($query) use ($startDate, $endDate) {
                $query->whereBetween('tickets.created_at', [$startDate, $endDate]); // PRÉCISER LA TABLE
            })
            ->count();

        // Nombre de commandes
        $totalOrders = Order::whereHas('orderItems.ticketType.event', function($query) use ($promoteurId) {
                $query->where('promoter_id', $promoteurId);
            })
            ->where('payment_status', 'paid')
            ->when($startDate, function($query) use ($startDate, $endDate) {
                $query->whereBetween('orders.created_at', [$startDate, $endDate]); // PRÉCISER LA TABLE
            })
            ->count();

        // Commissions (même requête que dashboard)
        $pendingCommissions = Commission::where('promoter_id', $promoteurId)
            ->where('status', 'pending')
            ->when($startDate, function($query) use ($startDate, $endDate) {
                $query->whereBetween('commissions.created_at', [$startDate, $endDate]); // PRÉCISER LA TABLE
            })
            ->sum('commission_amount');

        $totalCommissions = Commission::where('promoter_id', $promoteurId)
            ->when($startDate, function($query) use ($startDate, $endDate) {
                $query->whereBetween('commissions.created_at', [$startDate, $endDate]); // PRÉCISER LA TABLE
            })
            ->sum('commission_amount');

        // Nombre d'événements
        $totalEvents = Event::where('promoter_id', $promoteurId)
            ->when($startDate, function($query) use ($startDate, $endDate) {
                $query->whereBetween('events.created_at', [$startDate, $endDate]); // PRÉCISER LA TABLE
            })
            ->count();

        $publishedEvents = Event::where('promoter_id', $promoteurId)
            ->where('status', 'published')
            ->when($startDate, function($query) use ($startDate, $endDate) {
                $query->whereBetween('events.created_at', [$startDate, $endDate]); // PRÉCISER LA TABLE
            })
            ->count();

        return [
            'total_revenue' => $totalRevenue,
            'total_tickets_sold' => $totalTicketsSold,
            'total_orders' => $totalOrders,
            'total_commissions' => $totalCommissions,
            'pending_commissions' => $pendingCommissions,
            'total_events' => $totalEvents,
            'published_events' => $publishedEvents,
            'average_order_value' => $totalOrders > 0 ? $totalRevenue / $totalOrders : 0,
        ];
    }

    /**
     * Obtenir les commandes avec relations complètes - CORRIGÉ AMBIGUÏTÉ SQL
     */
    private function getOrders($promoteurId, $startDate, $endDate)
    {
        return Order::with([
            'orderItems.ticketType.event',
            'user'
        ])
        ->whereHas('orderItems.ticketType.event', function($query) use ($promoteurId) {
            $query->where('promoter_id', $promoteurId);
        })
        ->where('payment_status', 'paid')
        ->when($startDate, function($query) use ($startDate, $endDate) {
            $query->whereBetween('orders.created_at', [$startDate, $endDate]); // PRÉCISER LA TABLE
        })
        ->orderBy('orders.created_at', 'desc') // PRÉCISER LA TABLE
        ->paginate(20);
    }

    /**
     * Obtenir les événements avec données de ventes - CORRIGÉ AMBIGUÏTÉ SQL
     */
    private function getEventsWithSales($promoteurId, $startDate, $endDate)
    {
        // Utiliser les relations qui existent vraiment dans Event.php
        return Event::where('promoter_id', $promoteurId)
            ->with(['ticketTypes', 'category', 'orders' => function($query) use ($startDate, $endDate) {
                $query->where('payment_status', 'paid');
                if ($startDate) {
                    $query->whereBetween('orders.created_at', [$startDate, $endDate]); // PRÉCISER LA TABLE
                }
            }])
            ->get()
            ->map(function($event) use ($startDate, $endDate) {
                // Calculer les statistiques pour chaque événement
                $ordersQuery = $event->orders()->where('payment_status', 'paid');
                
                if ($startDate) {
                    $ordersQuery->whereBetween('orders.created_at', [$startDate, $endDate]); // PRÉCISER LA TABLE
                }
                
                $ordersForPeriod = $ordersQuery->get();
                $revenue = $ordersForPeriod->sum('total_amount');
                $ordersCount = $ordersForPeriod->count();
                
                // Billets vendus via les tickets - CORRECTION AMBIGUÏTÉ
                $ticketsSoldQuery = $event->tickets()->where('tickets.status', '!=', 'available'); // PRÉCISER LA TABLE
                
                if ($startDate) {
                    $ticketsSoldQuery->whereBetween('tickets.created_at', [$startDate, $endDate]); // PRÉCISER LA TABLE
                }
                
                $ticketsSold = $ticketsSoldQuery->count();

                return [
                    'event' => $event,
                    'revenue' => $revenue,
                    'orders_count' => $ordersCount,
                    'tickets_sold' => $ticketsSold,
                ];
            })
            ->filter(function($item) {
                return $item['revenue'] > 0 || $item['tickets_sold'] > 0;
            })
            ->sortByDesc('revenue');
    }

    /**
     * Obtenir les ventes journalières pour le graphique - CORRIGÉ AMBIGUÏTÉ SQL
     */
    private function getDailySales($promoteurId, $startDate, $endDate)
    {
        if (!$startDate) {
            $startDate = now()->subDays(7); // 7 derniers jours par défaut
        }

        // Retourner des objets compatibles avec la vue qui utilise $day->date et $day->revenue
        $dailySales = collect();
        $currentDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);

        while ($currentDate->lte($endDate)) {
            // Même requête que dans weeklyStats du dashboard - CORRECTION AMBIGUÏTÉ
            $revenue = Order::whereHas('orderItems.ticketType.event', function($query) use ($promoteurId) {
                    $query->where('promoter_id', $promoteurId);
                })
                ->where('payment_status', 'paid')
                ->whereDate('orders.created_at', $currentDate) // PRÉCISER LA TABLE
                ->sum('total_amount');

            // Créer un objet stdClass pour que la vue puisse utiliser $day->date
            $dayObject = new \stdClass();
            $dayObject->date = $currentDate->format('Y-m-d');
            $dayObject->revenue = $revenue;
            $dayObject->formatted_date = $currentDate->format('d/m');

            $dailySales->push($dayObject);

            $currentDate->addDay();
        }

        return $dailySales;
    }

    /**
     * Obtenir les ventes par événement - CORRIGÉ AMBIGUÏTÉ SQL  
     */
    private function getSalesByEvent($promoteurId, $startDate, $endDate)
    {
        // Récupérer les événements avec leurs relations existantes
        $events = Event::where('promoter_id', $promoteurId)
            ->with(['ticketTypes', 'category', 'orders'])
            ->get();

        return $events->map(function($event) use ($startDate, $endDate) {
            // Utiliser la relation orders() qui existe dans Event.php
            $ordersQuery = $event->orders()->where('payment_status', 'paid');
            
            if ($startDate) {
                $ordersQuery->whereBetween('orders.created_at', [$startDate, $endDate]); // PRÉCISER LA TABLE
            }
            
            $orders = $ordersQuery->get();
            $revenue = $orders->sum('total_amount');
            $ordersCount = $orders->count();

            // Utiliser la relation tickets() - CORRECTION AMBIGUÏTÉ SQL
            $ticketsSoldQuery = $event->tickets()->where('tickets.status', '!=', 'available'); // PRÉCISER LA TABLE
            
            if ($startDate) {
                $ticketsSoldQuery->whereBetween('tickets.created_at', [$startDate, $endDate]); // PRÉCISER LA TABLE
            }
            
            $ticketsSold = $ticketsSoldQuery->count();

            return [
                'event' => $event,
                'revenue' => $revenue,
                'orders_count' => $ordersCount,
                'tickets_sold' => $ticketsSold,
            ];
        })->filter(function($item) {
            // Ne garder que les événements avec des ventes
            return $item['revenue'] > 0 || $item['tickets_sold'] > 0;
        })->sortByDesc('revenue'); // Trier par revenus décroissants
    }

    /**
     * Définir la date de début selon la période
     */
    private function getStartDate($period)
    {
        switch ($period) {
            case 'today':
                return now()->startOfDay();
            case 'week':
                return now()->startOfWeek();
            case 'month':
                return now()->startOfMonth();
            case 'year':
                return now()->startOfYear();
            default:
                return null; // Toutes les données
        }
    }

    /**
     * Statistiques par défaut en cas d'erreur - MISE À JOUR COMPLÈTE
     */
    private function getDefaultStats()
    {
        return [
            'total_revenue' => 0,
            'total_tickets_sold' => 0,
            'total_orders' => 0,
            'total_commissions' => 0,
            'pending_commissions' => 0,
            'total_events' => 0,
            'published_events' => 0,
            'average_order_value' => 0,
        ];
    }

    /**
     * NOUVELLE MÉTHODE : Formatter les dates pour la vue
     */
    private function formatDatesForView($startDate, $endDate, $period)
    {
        // Dates par défaut selon la période
        if (!$startDate || !$endDate) {
            switch ($period) {
                case 'today':
                    $startDate = now()->startOfDay();
                    $endDate = now()->endOfDay();
                    break;
                case 'week':
                    $startDate = now()->startOfWeek();
                    $endDate = now()->endOfWeek();
                    break;
                case 'month':
                    $startDate = now()->startOfMonth();
                    $endDate = now()->endOfMonth();
                    break;
                case 'year':
                    $startDate = now()->startOfYear();
                    $endDate = now()->endOfYear();
                    break;
                default:
                    $startDate = now()->startOfMonth();
                    $endDate = now()->endOfMonth();
            }
        }

        return [
            'startDate' => $startDate,
            'endDate' => $endDate
        ];
    }

    /**
     * Page des rapports détaillés
     */
    public function reports(Request $request)
    {
        $promoteurId = Auth::id();
        $type = $request->get('type', 'overview');
        $period = $request->get('period', 'month');
        
        try {
            $startDate = $this->getStartDate($period);
            $endDate = now();
            
            $data = [
                'stats' => $this->getSalesStats($promoteurId, $startDate, $endDate),
                'topEvents' => $this->getEventsWithSales($promoteurId, $startDate, $endDate)->take(10),
                'dailySales' => $this->getDailySales($promoteurId, $startDate, $endDate),
                'period' => $period,
                'type' => $type
            ];

            return view('promoteur.sales.reports', compact('data', 'type', 'period'));
            
        } catch (\Exception $e) {
            Log::error('Erreur dans SalesController@reports: ' . $e->getMessage());
            
            return back()->with('error', 'Erreur lors du chargement des rapports');
        }
    }
}