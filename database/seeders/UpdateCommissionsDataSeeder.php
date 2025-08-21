<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Commission;
use App\Models\Order;

class UpdateCommissionsDataSeeder extends Seeder
{
    public function run()
    {
        // Mettre à jour les commissions qui n'ont pas de promoter_id
        $commissionsWithoutPromoter = Commission::whereNull('promoter_id')
            ->with('order.event')
            ->get();
            
        foreach ($commissionsWithoutPromoter as $commission) {
            if ($commission->order && $commission->order->event && $commission->order->event->promoter_id) {
                $commission->update([
                    'promoter_id' => $commission->order->event->promoter_id
                ]);
            }
        }
        
        // Recalculer les montants pour les commissions existantes si nécessaire
        $commissionsToRecalculate = Commission::where('commission_amount', 0)
            ->orWhere('net_amount', 0)
            ->get();
            
        foreach ($commissionsToRecalculate as $commission) {
            if ($commission->gross_amount > 0 && $commission->commission_rate > 0) {
                $calculation = Commission::calculateCommission(
                    $commission->gross_amount, 
                    $commission->commission_rate
                );
                
                $commission->update([
                    'commission_amount' => $calculation['commission_amount'],
                    'net_amount' => $calculation['net_amount']
                ]);
            }
        }
        
        $this->command->info('Données des commissions mises à jour avec succès.');
    }
}
