<?php

namespace App\Components;

use App\Models\Event;
use App\Models\CommissionSetting;
use App\Helpers\CurrencyHelper;

class CommissionCalculator
{
    /**
     * Calculer les commissions pour un événement et un prix
     */
    public static function calculateForEvent($event, $price, $quantity = 1)
    {
        $totalAmount = $price * $quantity;
        
        // Obtenir les paramètres de commission
        $commissionSetting = CommissionSetting::getCommissionForEvent($event, $event->promoteur_id);
        
        if (!$commissionSetting) {
            // Valeurs par défaut si pas de paramètres
            $commissionRate = 10.0; // 10%
            $platformFee = 500; // 500 FCFA par billet
        } else {
            $commissionRate = $commissionSetting->commission_rate;
            $platformFee = $commissionSetting->platform_fee_fixed ?? 500;
        }
        
        // Calculer les frais totaux
        $totalPlatformFees = $platformFee * $quantity;
        
        // Calculer la commission
        $commissionData = CurrencyHelper::calculateCommission(
            $totalAmount, 
            $commissionRate, 
            $totalPlatformFees
        );
        
        return [
            'gross_amount' => $totalAmount,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionData['commission'],
            'net_amount' => $commissionData['net'],
            'platform_fee_per_ticket' => $platformFee,
            'total_platform_fees' => $totalPlatformFees,
            'promoter_percentage' => round((($commissionData['net'] / $totalAmount) * 100), 1),
            'platform_percentage' => round((($commissionData['commission'] / $totalAmount) * 100), 1),
        ];
    }
    
    /**
     * Obtenir un résumé formaté pour affichage
     */
    public static function getFormattedSummary($event, $price, $quantity = 1)
    {
        $calc = self::calculateForEvent($event, $price, $quantity);
        
        return [
            'price_display' => CurrencyHelper::formatFCFA($calc['gross_amount']),
            'promoter_earnings' => CurrencyHelper::formatFCFA($calc['net_amount']),
            'platform_commission' => CurrencyHelper::formatFCFA($calc['commission_amount']),
            'commission_rate' => $calc['commission_rate'] . '%',
            'promoter_percentage' => $calc['promoter_percentage'] . '%',
            'platform_percentage' => $calc['platform_percentage'] . '%',
            'breakdown' => [
                'gross' => $calc['gross_amount'],
                'net' => $calc['net_amount'],
                'commission' => $calc['commission_amount'],
                'fees' => $calc['total_platform_fees']
            ]
        ];
    }
    
    /**
     * Générer le HTML pour l'affichage des commissions
     */
    public static function generateCommissionWidget($event, $price, $quantity = 1)
    {
        $summary = self::getFormattedSummary($event, $price, $quantity);
        
        return view('components.commission-widget', [
            'summary' => $summary,
            'event' => $event,
            'price' => $price,
            'quantity' => $quantity
        ])->render();
    }
    
    /**
     * Calculer les gains estimés pour un promoteur sur une période
     */
    public static function estimateMonthlyEarnings($promoterId, $averageEventPrice = 5000, $estimatedTicketsPerEvent = 50, $eventsPerMonth = 2)
    {
        // Obtenir les paramètres de commission pour ce promoteur
        $dummyEvent = new Event(['promoteur_id' => $promoterId]);
        $calc = self::calculateForEvent($dummyEvent, $averageEventPrice, $estimatedTicketsPerEvent);
        
        $earningsPerEvent = $calc['net_amount'];
        $monthlyEarnings = $earningsPerEvent * $eventsPerMonth;
        
        return [
            'per_event' => CurrencyHelper::formatFCFA($earningsPerEvent),
            'per_month' => CurrencyHelper::formatFCFA($monthlyEarnings),
            'commission_rate' => $calc['commission_rate'] . '%',
            'breakdown' => [
                'revenue_per_event' => CurrencyHelper::formatFCFA($averageEventPrice * $estimatedTicketsPerEvent),
                'tickets_per_event' => $estimatedTicketsPerEvent,
                'events_per_month' => $eventsPerMonth,
                'net_per_event' => $earningsPerEvent,
                'net_per_month' => $monthlyEarnings
            ]
        ];
    }
}

// ========== BLADE COMPONENT ==========

// File: resources/views/components/commission-widget.blade.php
?>

<div class="commission-widget">
    <div class="card border-success">
        <div class="card-header bg-success text-white">
            <h6 class="mb-0">
                <i class="fas fa-calculator me-2"></i>
                Vos gains sur cette vente
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="text-muted small">Prix de vente</label>
                        <div class="h5 mb-0">{{ $summary['price_display'] }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="text-muted small">Vos gains ({{ $summary['promoter_percentage'] }})</label>
                        <div class="h5 mb-0 text-success">{{ $summary['promoter_earnings'] }}</div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="text-muted small">Commission plateforme ({{ $summary['platform_percentage'] }})</label>
                        <div class="text-primary">{{ $summary['platform_commission'] }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="text-muted small">Taux de commission</label>
                        <div class="text-info">{{ $summary['commission_rate'] }}</div>
                    </div>
                </div>
            </div>
            
            <!-- Barre de progression visuelle -->
            <div class="progress mb-2" style="height: 20px;">
                <div class="progress-bar bg-success" role="progressbar" 
                     style="width: {{ $summary['promoter_percentage'] }}%">
                    {{ $summary['promoter_percentage'] }}
                </div>
                <div class="progress-bar bg-primary" role="progressbar" 
                     style="width: {{ $summary['platform_percentage'] }}%">
                    {{ $summary['platform_percentage'] }}
                </div>
            </div>
            <div class="d-flex justify-content-between small text-muted">
                <span>Vos gains</span>
                <span>Commission plateforme</span>
            </div>
        </div>
    </div>
</div>

<style>
.commission-widget .progress-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 0.8rem;
}
</style>

<?php
// ========== MISE À JOUR DU CHECKOUT CONTROLLER ==========

// Dans votre CheckoutController, ajoutez la création automatique des commissions :

namespace App\Http\Controllers;

class CheckoutController extends Controller
{
    public function process(Request $request)
    {
        // ... votre code existant ...
        
        try {
            DB::beginTransaction();

            foreach ($eventGroups as $eventId => $items) {
                $eventTotal = array_sum(array_column($items, 'total_price'));
                
                // Créer la commande
                $order = Order::create([
                    'user_id' => Auth::id(),
                    'event_id' => $eventId,
                    'total_amount' => $eventTotal,
                    'payment_status' => 'pending',
                    'payment_method' => 'manual',
                    'order_number' => Order::generateOrderNumber(),
                    'billing_email' => $request->billing_email,
                    'billing_phone' => $request->billing_phone,
                ]);

                // Créer les items et billets...
                // ... votre code existant ...

                // NOUVEAU : Créer automatiquement la commission
                $this->createCommissionForOrder($order);

                

                // Marquer comme payé
                $order->markAsPaid('MANUAL-' . time());
                
                $orders[] = $order;
            }

            DB::commit();
            
            return redirect()->route('acheteur.dashboard')
                ->with('success', 'Commande validée avec succès ! Vos billets sont maintenant disponibles.');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Erreur lors de la validation : ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Créer la commission pour une commande
     */
    private function createCommissionForOrder($order)
    {
        $event = $order->event;
        
        // Calculer la commission
        $commissionData = $order->calculateCommission();
        
        // Créer l'enregistrement de commission
        Commission::create([
            'order_id' => $order->id,
            'promoteur_id' => $event->promoteur_id,
            'gross_amount' => $commissionData['gross_amount'],
            'commission_rate' => $commissionData['commission_rate'],
            'commission_amount' => $commissionData['commission_amount'],
            'net_amount' => $commissionData['net_amount'],
            'platform_fee' => $commissionData['platform_fee'],
            'status' => 'pending' // En attente de paiement
        ]);
        
        return true;
    }
}