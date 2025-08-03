<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\Commission;

class CommissionsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    private $filters;
    
    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }
    
    public function collection()
    {
        $query = Commission::with(['order.event', 'promoter']);
        
        if (isset($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }
        
        return $query->orderBy('created_at', 'desc')->get();
    }
    
    public function headings(): array
    {
        return [
            'Date', 'Commande', 'Promoteur', 'Email', 'Événement',
            'Revenus Bruts (FCFA)', 'Commission (%)', 'Commission (FCFA)',
            'Net Promoteur (FCFA)', 'Statut', 'Date Paiement'
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
            ucfirst($commission->status),
            $commission->paid_at ? $commission->paid_at->format('d/m/Y') : ''
        ];
    }
    
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 
                  'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['rgb' => 'FF6B35']]],
        ];
    }
}