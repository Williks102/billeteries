<?php
// SOLUTION 1: Créer le contrôleur manquant
// app/Http/Controllers/Promoteur/ReportsController.php

namespace App\Http\Controllers\Promoteur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\Commission;

class ReportsController extends Controller
{
    /**
     * Vue d'ensemble des rapports
     */
    public function index(Request $request)
    {
        $promoteurId = Auth::id();
        $type = $request->get('type', 'overview');
        $period = $request->get('period', 'month');
        
        try {
            // Calcul des dates
            $dates = $this->getDateRange($period);
            $startDate = $dates['startDate'];
            $endDate = $dates['endDate'];
            
            // Statistiques principales
            $stats = $this->getMainStats($promoteurId, $startDate, $endDate);
            
            // Données spécifiques selon le type de rapport
            $data = [];
            switch($type) {
                case 'events':
                    $data = $this->getEventsReport($promoteurId, $startDate, $endDate);
                    break;
                case 'financial':
                    $data = $this->getFinancialReport($promoteurId, $startDate, $endDate);
                    break;
                default:
                    $data = $this->getOverviewReport($promoteurId, $startDate, $endDate);
            }
            
            return view('promoteur.reports.index', compact('stats', 'data', 'type', 'period'));
            
        } catch (\Exception $e) {
            Log::error('Erreur rapports promoteur', [
                'promoteur_id' => $promoteurId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return view('promoteur.reports.index', [
                'stats' => $this->getDefaultStats(),
                'data' => [],
                'type' => $type,
                'period' => $period
            ])->with('error', 'Erreur lors du chargement des rapports');
        }
    }
    
    /**
     * Statistiques principales (CORRIGÉES)
     */
    private function getMainStats($promoteurId, $startDate, $endDate)
    {
        // Total événements
        $totalEvents = Event::where('promoter_id', $promoteurId)->count();
        
        // Événements publiés
        $publishedEvents = Event::where('promoter_id', $promoteurId)
            ->where('status', 'published')
            ->count();
        
        // Revenus totaux (REQUÊTE CORRIGÉE)
        $totalRevenue = DB::table('orders')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('ticket_types', 'order_items.ticket_type_id', '=', 'ticket_types.id')
            ->join('events', 'ticket_types.event_id', '=', 'events.id')
            ->where('events.promoter_id', $promoteurId)
            ->where('orders.payment_status', 'paid')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->sum('orders.total_amount');
        
        // Revenus période
        $periodRevenue = DB::table('orders')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('ticket_types', 'order_items.ticket_type_id', '=', 'ticket_types.id')
            ->join('events', 'ticket_types.event_id', '=', 'events.id')
            ->where('events.promoter_id', $promoteurId)
            ->where('orders.payment_status', 'paid')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->sum('orders.total_amount');
        
        // Billets vendus
        $ticketsSold = DB::table('tickets')
            ->join('ticket_types', 'tickets.ticket_type_id', '=', 'ticket_types.id')
            ->join('events', 'ticket_types.event_id', '=', 'events.id')
            ->where('events.promoter_id', $promoteurId)
            ->where('tickets.status', 'sold')
            ->whereBetween('tickets.created_at', [$startDate, $endDate])
            ->count();
        
        // Commandes
        $totalOrders = DB::table('orders')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('ticket_types', 'order_items.ticket_type_id', '=', 'ticket_types.id')
            ->join('events', 'ticket_types.event_id', '=', 'events.id')
            ->where('events.promoter_id', $promoteurId)
            ->where('orders.payment_status', 'paid')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->distinct('orders.id')
            ->count();
        
        // Commissions
        $totalCommissions = Commission::where('promoter_id', $promoteurId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('commission_amount');
        
        return [
            'total_events' => $totalEvents,
            'published_events' => $publishedEvents,
            'draft_events' => $totalEvents - $publishedEvents,
            'total_revenue' => $totalRevenue,
            'period_revenue' => $periodRevenue,
            'tickets_sold' => $ticketsSold,
            'total_orders' => $totalOrders,
            'average_order' => $totalOrders > 0 ? $totalRevenue / $totalOrders : 0,
            'total_commissions' => $totalCommissions,
            'pending_commissions' => Commission::where('promoter_id', $promoteurId)
                ->where('status', 'pending')
                ->sum('commission_amount'),
        ];
    }
    
    /**
     * Rapport par événements
     */
    private function getEventsReport($promoteurId, $startDate, $endDate)
    {
        $topEvents = DB::table('events')
            ->leftJoin('ticket_types', 'events.id', '=', 'ticket_types.event_id')
            ->leftJoin('order_items', 'ticket_types.id', '=', 'order_items.ticket_type_id')
            ->leftJoin('orders', function($join) use ($startDate, $endDate) {
                $join->on('order_items.order_id', '=', 'orders.id')
                     ->where('orders.payment_status', 'paid')
                     ->whereBetween('orders.created_at', [$startDate, $endDate]);
            })
            ->where('events.promoter_id', $promoteurId)
            ->select([
                'events.id',
                'events.title',
                'events.event_date',
                'events.status',
                DB::raw('COALESCE(SUM(order_items.total_price), 0) as total_revenue'),
                DB::raw('COUNT(DISTINCT orders.id) as orders_count'),
                DB::raw('COALESCE(SUM(order_items.quantity), 0) as tickets_sold')
            ])
            ->groupBy('events.id', 'events.title', 'events.event_date', 'events.status')
            ->orderBy('total_revenue', 'desc')
            ->get();
        
        return [
            'top_events' => $topEvents,
            'events_count' => $topEvents->count(),
            'best_event' => $topEvents->first(),
        ];
    }
    
    /**
     * Rapport financier
     */
    private function getFinancialReport($promoteurId, $startDate, $endDate)
    {
        // Évolution mensuelle
        $monthlyData = DB::table('orders')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('ticket_types', 'order_items.ticket_type_id', '=', 'ticket_types.id')
            ->join('events', 'ticket_types.event_id', '=', 'events.id')
            ->where('events.promoter_id', $promoteurId)
            ->where('orders.payment_status', 'paid')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->select([
                DB::raw('DATE_FORMAT(orders.created_at, "%Y-%m") as month'),
                DB::raw('SUM(orders.total_amount) as revenue'),
                DB::raw('COUNT(DISTINCT orders.id) as orders_count')
            ])
            ->groupBy('month')
            ->orderBy('month')
            ->get();
        
        return [
            'monthly_data' => $monthlyData,
            'growth_rate' => $this->calculateGrowthRate($monthlyData),
        ];
    }
    
    /**
     * Rapport vue d'ensemble
     */
    private function getOverviewReport($promoteurId, $startDate, $endDate)
    {
        return [
            'daily_sales' => $this->getDailySales($promoteurId, $startDate, $endDate),
            'recent_orders' => $this->getRecentOrders($promoteurId, 10),
        ];
    }
    
    /**
     * Ventes quotidiennes
     */
    private function getDailySales($promoteurId, $startDate, $endDate)
    {
        return DB::table('orders')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('ticket_types', 'order_items.ticket_type_id', '=', 'ticket_types.id')
            ->join('events', 'ticket_types.event_id', '=', 'events.id')
            ->where('events.promoter_id', $promoteurId)
            ->where('orders.payment_status', 'paid')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->select([
                DB::raw('DATE(orders.created_at) as date'),
                DB::raw('SUM(orders.total_amount) as revenue'),
                DB::raw('COUNT(DISTINCT orders.id) as orders_count')
            ])
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }
    
    /**
     * Commandes récentes
     */
    private function getRecentOrders($promoteurId, $limit = 10)
    {
        return DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('ticket_types', 'order_items.ticket_type_id', '=', 'ticket_types.id')
            ->join('events', 'ticket_types.event_id', '=', 'events.id')
            ->where('events.promoter_id', $promoteurId)
            ->where('orders.payment_status', 'paid')
            ->select([
                'orders.*',
                'users.name as customer_name',
                'events.title as event_title'
            ])
            ->orderBy('orders.created_at', 'desc')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Calcul des plages de dates
     */
    private function getDateRange($period)
    {
        switch($period) {
            case 'today':
                return [
                    'startDate' => now()->startOfDay(),
                    'endDate' => now()->endOfDay()
                ];
            case 'week':
                return [
                    'startDate' => now()->startOfWeek(),
                    'endDate' => now()->endOfWeek()
                ];
            case 'month':
                return [
                    'startDate' => now()->startOfMonth(),
                    'endDate' => now()->endOfMonth()
                ];
            case 'year':
                return [
                    'startDate' => now()->startOfYear(),
                    'endDate' => now()->endOfYear()
                ];
            default:
                return [
                    'startDate' => now()->startOfMonth(),
                    'endDate' => now()->endOfMonth()
                ];
        }
    }
    
    /**
     * Statistiques par défaut en cas d'erreur
     */
    private function getDefaultStats()
    {
        return [
            'total_events' => 0,
            'published_events' => 0,
            'draft_events' => 0,
            'total_revenue' => 0,
            'period_revenue' => 0,
            'tickets_sold' => 0,
            'total_orders' => 0,
            'average_order' => 0,
            'total_commissions' => 0,
            'pending_commissions' => 0,
        ];
    }
    
    /**
     * Calculer le taux de croissance
     */
    private function calculateGrowthRate($monthlyData)
    {
        if ($monthlyData->count() < 2) return 0;
        
        $current = $monthlyData->last()->revenue;
        $previous = $monthlyData->get($monthlyData->count() - 2)->revenue;
        
        if ($previous == 0) return 0;
        
        return (($current - $previous) / $previous) * 100;
    }
    
    /**
     * Export des données
     */
    public function export(Request $request)
    {
        $promoteurId = Auth::id();
        $type = $request->get('type', 'overview');
        $period = $request->get('period', 'month');
        
        // TODO: Implémenter l'export Excel/CSV
        return back()->with('info', 'Export en cours de développement');
    }
}