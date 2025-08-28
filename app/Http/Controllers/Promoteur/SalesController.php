<?php

namespace App\Http\Controllers\Promoteur;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Order;
use App\Models\Commission;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SalesController extends Controller
{
    /**
     * Page des ventes du promoteur
     */
    public function index(Request $request)
    {
        $promoteurId = Auth::id();
        $period = $request->get('period', 'all');
        
        // Query de base pour les commandes du promoteur
        $ordersQuery = Order::whereHas('orderItems.ticketType.event', function($query) use ($promoteurId) {
            $query->where('promoter_id', $promoteurId);
        })->where('payment_status', 'paid');

        // Filtrer par période
        switch ($period) {
            case 'today':
                $ordersQuery->whereDate('created_at', today());
                break;
            case 'week':
                $ordersQuery->where('created_at', '>=', now()->subDays(7));
                break;
            case 'month':
                $ordersQuery->where('created_at', '>=', now()->subMonth());
                break;
            case 'year':
                $ordersQuery->where('created_at', '>=', now()->subYear());
                break;
        }

        $orders = $ordersQuery->with(['user', 'orderItems.ticketType.event'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Statistiques pour la période
        $stats = $this->getSalesStats($promoteurId, $period);
        
        // Événements avec ventes pour le filtre
        $eventsWithSales = Event::where('promoter_id', $promoteurId)
            ->whereHas('ticketTypes.orderItems.order', function($query) {
                $query->where('status', 'paid');
            })
            ->withCount('orders')
            ->orderBy('orders_count', 'desc')
            ->get();

        return view('promoteur.sales.index', compact(
            'orders', 
            'stats', 
            'period',
            'eventsWithSales'
        ));
    }

    /**
     * Page des rapports détaillés
     */
    public function reports(Request $request)
    {
        $promoteurId = Auth::id();
        $type = $request->get('type', 'overview');
        $period = $request->get('period', 'month');
        
        $data = [];
        
        switch ($type) {
            case 'overview':
                $data = $this->getOverviewReport($promoteurId, $period);
                break;
            case 'events':
                $data = $this->getEventsReport($promoteurId, $period);
                break;
            case 'financial':
                $data = $this->getFinancialReport($promoteurId, $period);
                break;
            case 'commissions':
                $data = $this->getCommissionsReport($promoteurId, $period);
                break;
        }

        return view('promoteur.sales.reports', compact('data', 'type', 'period'));
    }

    /**
     * Exporter les données de ventes
     */
    public function export(Request $request)
    {
        $promoteurId = Auth::id();
        $format = $request->get('format', 'csv');
        $period = $request->get('period', 'month');
        $type = $request->get('type', 'sales');

        // Récupérer les données selon le type
        $data = $this->getExportData($promoteurId, $type, $period);

        switch ($format) {
            case 'csv':
                return $this->exportToCsv($data, $type);
            case 'excel':
                return $this->exportToExcel($data, $type);
            case 'pdf':
                return $this->exportToPdf($data, $type);
            default:
                return back()->with('error', 'Format d\'export non supporté');
        }
    }

    /**
     * Obtenir les statistiques de ventes - CORRIGÉ
     */
    private function getSalesStats($promoteurId, $period)
    {
        $query = Order::whereHas('orderItems.ticketType.event', function($query) use ($promoteurId) {
            $query->where('promoter_id', $promoteurId);
        })->where('payment_status', 'paid');

        // Appliquer le filtre de période
        switch ($period) {
            case 'today':
                $query->whereDate('created_at', today());
                break;
            case 'week':
                $query->where('created_at', '>=', now()->subDays(7));
                break;
            case 'month':
                $query->where('created_at', '>=', now()->subMonth());
                break;
        }

        $orders = $query->with('orderItems.ticketType')->get();

        $totalRevenue = $orders->sum('total_amount');
        $totalOrders = $orders->count();
        $totalTickets = $orders->sum(function($order) {
            return $order->orderItems->sum('quantity');
        });

        // Calcul des tickets utilisés - CORRIGÉ
        $ticketsUsed = Ticket::whereHas('ticketType.event', function($query) use ($promoteurId) {
                $query->where('promoter_id', $promoteurId);
            })
            ->where('status', 'used');

        // Appliquer le même filtre de période pour les tickets utilisés
        switch ($period) {
            case 'today':
                $ticketsUsed->whereDate('updated_at', today());
                break;
            case 'week':
                $ticketsUsed->where('updated_at', '>=', now()->subDays(7));
                break;
            case 'month':
                $ticketsUsed->where('updated_at', '>=', now()->subMonth());
                break;
        }

        $totalTicketsUsed = $ticketsUsed->count();

        return [
            'total_revenue' => $totalRevenue,
            'total_orders' => $totalOrders,
            'total_tickets' => $totalTickets,
            'tickets_used' => $totalTicketsUsed,
            'average_order_value' => $totalOrders > 0 ? round($totalRevenue / $totalOrders, 0) : 0,
            'usage_rate' => $totalTickets > 0 ? round(($totalTicketsUsed / $totalTickets) * 100, 1) : 0
        ];
    }

    /**
     * Rapport d'aperçu général
     */
    private function getOverviewReport($promoteurId, $period)
    {
        $stats = $this->getSalesStats($promoteurId, $period);
        
        // Top événements
        $topEvents = Event::where('promoter_id', $promoteurId)
            ->withSum(['orders as revenue' => function($query) {
                $query->where('payment_status', 'paid');
            }], 'total_amount')
            ->orderBy('revenue', 'desc')
            ->take(10)
            ->get();

        // Évolution des ventes (graphique)
        $salesEvolution = $this->getSalesEvolution($promoteurId, $period);

        return compact('stats', 'topEvents', 'salesEvolution');
    }

    /**
     * Rapport par événements - CORRIGÉ
     */
    private function getEventsReport($promoteurId, $period)
    {
        $events = Event::where('promoter_id', $promoteurId)
            ->with(['ticketTypes.orderItems.order'])
            ->get()
            ->map(function($event) {
                $orders = $event->orders()->where('payment_status', 'paid')->get();
                $allTickets = $event->ticketTypes->flatMap->tickets;
                
                return [
                    'event' => $event,
                    'total_revenue' => $orders->sum('total_amount'),
                    'total_orders' => $orders->count(),
                    'total_tickets' => $orders->sum(function($order) {
                        return $order->orderItems->sum('quantity');
                    }),
                    'tickets_used' => $allTickets->where('status', 'used')->count(),
                    'tickets_unused' => $allTickets->where('status', 'sold')->count()
                ];
            })
            ->sortByDesc('total_revenue');

        return compact('events');
    }

    /**
     * Rapport financier
     */
    private function getFinancialReport($promoteurId, $period)
    {
        $commissions = Commission::where('promoter_id', $promoteurId)
            ->with('event')
            ->orderBy('created_at', 'desc')
            ->get();

        $monthlyBreakdown = $commissions->groupBy(function($commission) {
            return $commission->created_at->format('Y-m');
        })->map(function($group) {
            return [
                'total' => $group->sum('commission_amount'),
                'paid' => $group->where('status', 'paid')->sum('commission_amount'),
                'pending' => $group->where('status', 'pending')->sum('commission_amount')
            ];
        });

        return compact('commissions', 'monthlyBreakdown');
    }

    /**
     * Rapport des commissions
     */
    private function getCommissionsReport($promoteurId, $period)
    {
        return Commission::where('promoter_id', $promoteurId)
            ->with(['event', 'order'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);
    }

    /**
     * Obtenir l'évolution des ventes pour graphique
     */
    private function getSalesEvolution($promoteurId, $period)
    {
        $days = match($period) {
            'week' => 7,
            'month' => 30,
            'year' => 365,
            default => 30
        };

        $evolution = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            
            $revenue = Order::whereHas('orderItems.ticketType.event', function($query) use ($promoteurId) {
                    $query->where('promoter_id', $promoteurId);
                })
                ->where('payment_status', 'paid')
                ->whereDate('created_at', $date)
                ->sum('total_amount');
                
            $evolution[] = [
                'date' => $date->format($days > 31 ? 'M d' : 'd/m'),
                'revenue' => $revenue
            ];
        }

        return $evolution;
    }

    /**
     * Obtenir les données pour export
     */
    private function getExportData($promoteurId, $type, $period)
    {
        switch ($type) {
            case 'sales':
                return $this->getSalesExportData($promoteurId, $period);
            case 'commissions':
                return $this->getCommissionsExportData($promoteurId, $period);
            case 'tickets':
                return $this->getTicketsExportData($promoteurId, $period);
            default:
                return [];
        }
    }

    /**
     * Export CSV
     */
    private function exportToCsv($data, $type)
    {
        $filename = "promoteur_{$type}_" . now()->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($data, $type) {
            $file = fopen('php://output', 'w');
            
            // En-têtes selon le type
            $headers = $this->getCsvHeaders($type);
            fputcsv($file, $headers);
            
            // Données
            foreach ($data as $row) {
                fputcsv($file, $this->formatCsvRow($row, $type));
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Obtenir les en-têtes CSV selon le type
     */
    private function getCsvHeaders($type)
    {
        return match($type) {
            'sales' => ['Date', 'Événement', 'Client', 'Email', 'Montant', 'Billets', 'Statut'],
            'commissions' => ['Date', 'Événement', 'Commande', 'Montant', 'Commission', 'Statut'],
            'tickets' => ['Code', 'Événement', 'Type', 'Client', 'Statut', 'Utilisé le'],
            default => []
        };
    }

    /**
     * Formater une ligne pour CSV - CORRIGÉ
     */
    private function formatCsvRow($row, $type)
    {
        return match($type) {
            'sales' => [
                $row->created_at->format('d/m/Y'),
                $row->orderItems->first()->ticketType->event->title ?? '',
                $row->user->name,
                $row->user->email,
                number_format($row->total_amount, 0),
                $row->orderItems->sum('quantity'),
                $row->status
            ],
            'tickets' => [
                $row->ticket_code,
                $row->ticketType->event->title,
                $row->ticketType->name,
                $row->orderItem->order->user->name ?? 'N/A',
                $row->status,
                $row->status === 'used' ? $row->updated_at->format('d/m/Y H:i') : ''
            ],
            default => []
        };
    }

    /**
     * Données d'export pour les ventes
     */
    private function getSalesExportData($promoteurId, $period)
    {
        $query = Order::whereHas('orderItems.ticketType.event', function($query) use ($promoteurId) {
            $query->where('promoter_id', $promoteurId);
        })->where('payment_status', 'paid');

        // Appliquer le filtre de période
        $this->applyPeriodFilter($query, $period);

        return $query->with(['user', 'orderItems.ticketType.event'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Données d'export pour les tickets - CORRIGÉ
     */
    private function getTicketsExportData($promoteurId, $period)
    {
        $query = Ticket::whereHas('ticketType.event', function($query) use ($promoteurId) {
            $query->where('promoter_id', $promoteurId);
        });

        $this->applyPeriodFilter($query, $period);

        return $query->with(['ticketType.event', 'orderItem.order.user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Appliquer un filtre de période à une query
     */
    private function applyPeriodFilter($query, $period)
    {
        switch ($period) {
            case 'today':
                $query->whereDate('created_at', today());
                break;
            case 'week':
                $query->where('created_at', '>=', now()->subDays(7));
                break;
            case 'month':
                $query->where('created_at', '>=', now()->subMonth());
                break;
            case 'year':
                $query->where('created_at', '>=', now()->subYear());
                break;
        }
    }

    // Méthodes d'export Excel et PDF à implémenter si nécessaire
    private function exportToExcel($data, $type)
    {
        // Implémentation Excel avec PhpSpreadsheet si besoin
        return $this->exportToCsv($data, $type);
    }

    private function exportToPdf($data, $type)
    {
        // Implémentation PDF avec DomPDF si besoin
        return $this->exportToCsv($data, $type);
    }
}