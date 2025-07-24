<?php

namespace App\Services;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CommissionsExport;
use App\Exports\RevenuesExport;
use App\Exports\OrdersExport;
use App\Exports\PromotersExport;
use App\Models\Commission;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;

class ExcelExportService
{
    /**
     * Exporter les commissions
     */
    public function exportCommissions($filters = [])
    {
        $filename = 'commissions_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        
        return Excel::download(new CommissionsExport($filters), $filename);
    }
    
    /**
     * Exporter les revenus par période
     */
    public function exportRevenues($startDate, $endDate)
    {
        $filename = 'revenus_' . $startDate . '_au_' . $endDate . '.xlsx';
        
        return Excel::download(new RevenuesExport($startDate, $endDate), $filename);
    }
    
    /**
     * Exporter les commandes
     */
    public function exportOrders($filters = [])
    {
        $filename = 'commandes_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        
        return Excel::download(new OrdersExport($filters), $filename);
    }
    
    /**
     * Exporter les performances des promoteurs
     */
    public function exportPromoters($period = 'this_month')
    {
        $filename = 'promoteurs_performance_' . $period . '_' . now()->format('Y-m-d') . '.xlsx';
        
        return Excel::download(new PromotersExport($period), $filename);
    }
    
    /**
     * Export complet pour comptabilité
     */
    public function exportAccountingReport($startDate, $endDate)
    {
        $filename = 'rapport_comptable_' . $startDate . '_au_' . $endDate . '.xlsx';
        
        // Créer un export multi-onglets
        return Excel::download(new class($startDate, $endDate) implements \Maatwebsite\Excel\Concerns\WithMultipleSheets {
            private $startDate;
            private $endDate;
            
            public function __construct($startDate, $endDate) {
                $this->startDate = $startDate;
                $this->endDate = $endDate;
            }
            
            public function sheets(): array {
                return [
                    'Revenus' => new RevenuesExport($this->startDate, $this->endDate),
                    'Commissions' => new CommissionsExport([
                        'start_date' => $this->startDate,
                        'end_date' => $this->endDate
                    ]),
                    'Commandes' => new OrdersExport([
                        'start_date' => $this->startDate,
                        'end_date' => $this->endDate,
                        'status' => 'paid'
                    ]),
                    'Promoteurs' => new PromotersExport('custom', $this->startDate, $this->endDate),
                ];
            }
        }, $filename);
    }
}

// ========== EXPORTS CLASSES ==========

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\Commission;
use App\Helpers\CurrencyHelper;

class CommissionsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    private $filters;
    
    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }
    
    public function collection()
    {
        $query = Commission::with(['order.event', 'promoteur']);
        
        // Appliquer les filtres
        if (isset($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }
        
        if (isset($this->filters['start_date'])) {
            $query->where('created_at', '>=', $this->filters['start_date']);
        }
        
        if (isset($this->filters['end_date'])) {
            $query->where('created_at', '<=', $this->filters['end_date']);
        }
        
        if (isset($this->filters['promoteur_id'])) {
            $query->where('promoteur_id', $this->filters['promoteur_id']);
        }
        
        return $query->orderBy('created_at', 'desc')->get();
    }
    
    public function headings(): array
    {
        return [
            'Date',
            'Commande',
            'Promoteur',
            'Email Promoteur',
            'Événement',
            'Revenus Bruts (FCFA)',
            'Taux Commission (%)',
            'Commission Plateforme (FCFA)',
            'Net Promoteur (FCFA)',
            'Frais Plateforme (FCFA)',
            'Statut',
            'Date Paiement'
        ];
    }
    
    public function map($commission): array
    {
        return [
            $commission->created_at->format('d/m/Y H:i'),
            $commission->order->order_number,
            $commission->promoteur->name,
            $commission->promoteur->email,
            $commission->order->event->title,
            $commission->gross_amount,
            $commission->commission_rate,
            $commission->commission_amount,
            $commission->net_amount,
            $commission->platform_fee,
            ucfirst($commission->status),
            $commission->paid_at ? $commission->paid_at->format('d/m/Y') : ''
        ];
    }
    
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['rgb' => 'FF6B35']]],
        ];
    }
}

class RevenuesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    private $startDate;
    private $endDate;
    
    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }
    
    public function collection()
    {
        return Order::with(['event.category', 'event.promoteur', 'user'])
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->orderBy('created_at', 'desc')
            ->get();
    }
    
    public function headings(): array
    {
        return [
            'Date',
            'Commande',
            'Client',
            'Email Client',
            'Événement',
            'Catégorie',
            'Promoteur',
            'Montant (FCFA)',
            'Commission Plateforme (FCFA)',
            'Net Promoteur (FCFA)',
            'Statut Paiement'
        ];
    }
    
    public function map($order): array
    {
        $commission = $order->commission;
        
        return [
            $order->created_at->format('d/m/Y H:i'),
            $order->order_number,
            $order->user->name,
            $order->billing_email,
            $order->event->title,
            $order->event->category->name,
            $order->event->promoteur->name,
            $order->total_amount,
            $commission ? $commission->commission_amount : 0,
            $commission ? $commission->net_amount : 0,
            ucfirst($order->payment_status)
        ];
    }
    
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['rgb' => 'FF6B35']]],
        ];
    }
}

class OrdersExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    private $filters;
    
    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }
    
    public function collection()
    {
        $query = Order::with(['event.category', 'user', 'orderItems.ticketType']);
        
        if (isset($this->filters['status'])) {
            $query->where('payment_status', $this->filters['status']);
        }
        
        if (isset($this->filters['start_date'])) {
            $query->where('created_at', '>=', $this->filters['start_date']);
        }
        
        if (isset($this->filters['end_date'])) {
            $query->where('created_at', '<=', $this->filters['end_date']);
        }
        
        return $query->orderBy('created_at', 'desc')->get();
    }
    
    public function headings(): array
    {
        return [
            'Date',
            'Numéro Commande',
            'Client',
            'Email',
            'Téléphone',
            'Événement',
            'Catégorie',
            'Nombre Billets',
            'Montant Total (FCFA)',
            'Statut Paiement',
            'Méthode Paiement',
            'Référence Paiement'
        ];
    }
    
    public function map($order): array
    {
        return [
            $order->created_at->format('d/m/Y H:i'),
            $order->order_number,
            $order->user->name,
            $order->billing_email,
            $order->billing_phone,
            $order->event->title,
            $order->event->category->name,
            $order->orderItems->sum('quantity'),
            $order->total_amount,
            ucfirst($order->payment_status),
            $order->payment_method,
            $order->payment_reference
        ];
    }
    
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['rgb' => 'FF6B35']]],
        ];
    }
}

class PromotersExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    private $period;
    private $startDate;
    private $endDate;
    
    public function __construct($period = 'this_month', $startDate = null, $endDate = null)
    {
        $this->period = $period;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }
    
    public function collection()
    {
        $dateRange = $this->getDateRange();
        
        return User::where('role', 'promoteur')
            ->withCount(['events as total_events'])
            ->withCount(['events as period_events' => function ($query) use ($dateRange) {
                $query->whereBetween('created_at', $dateRange);
            }])
            ->withSum(['commissions as total_earned'], 'net_amount')
            ->withSum(['commissions as period_earned' => function ($query) use ($dateRange) {
                $query->whereBetween('created_at', $dateRange);
            }], 'net_amount')
            ->withSum(['commissions as platform_earned' => function ($query) use ($dateRange) {
                $query->whereBetween('created_at', $dateRange);
            }], 'commission_amount')
            ->orderByDesc('period_earned')
            ->get();
    }
    
    public function headings(): array
    {
        return [
            'Promoteur',
            'Email',
            'Téléphone',
            'Date Inscription',
            'Total Événements',
            'Événements Période',
            'Revenus Total (FCFA)',
            'Revenus Période (FCFA)',
            'Commission Plateforme (FCFA)',
            'Statut'
        ];
    }
    
    public function map($promoter): array
    {
        return [
            $promoter->name,
            $promoter->email,
            $promoter->phone,
            $promoter->created_at->format('d/m/Y'),
            $promoter->total_events,
            $promoter->period_events,
            $promoter->total_earned ?? 0,
            $promoter->period_earned ?? 0,
            $promoter->platform_earned ?? 0,
            $promoter->period_events > 0 ? 'Actif' : 'Inactif'
        ];
    }
    
    private function getDateRange()
    {
        if ($this->startDate && $this->endDate) {
            return [$this->startDate, $this->endDate];
        }
        
        switch ($this->period) {
            case 'this_month':
                return [now()->startOfMonth(), now()->endOfMonth()];
            case 'last_month':
                return [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()];
            case 'this_year':
                return [now()->startOfYear(), now()->endOfYear()];
            default:
                return [now()->startOfMonth(), now()->endOfMonth()];
        }
    }
    
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['rgb' => 'FF6B35']]],
        ];
    }
}