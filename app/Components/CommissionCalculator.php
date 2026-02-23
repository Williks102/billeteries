<?php

namespace App\Components;

use App\Helpers\CurrencyHelper;
use App\Models\CommissionSetting;
use App\Models\Event;

class CommissionCalculator
{
    /**
     * Calculer les commissions pour un événement et un prix.
     */
    public static function calculateForEvent($event, $price, $quantity = 1)
    {
        $totalAmount = $price * $quantity;

        if ($totalAmount == 0) {
            return [
                'gross_amount' => 0,
                'commission_rate' => 0,
                'commission_amount' => 0,
                'net_amount' => 0,
                'platform_fee_per_ticket' => 0,
                'total_platform_fees' => 0,
                'promoter_percentage' => 100,
                'platform_percentage' => 0,
                'is_free' => true,
            ];
        }

        $commissionSetting = CommissionSetting::getCommissionForEvent($event, $event->promoter_id);

        if (! $commissionSetting) {
            $commissionRate = 10.0;
            $platformFee = 500;
        } else {
            $commissionRate = $commissionSetting->commission_rate;
            $platformFee = $commissionSetting->platform_fee_fixed ?? 500;
        }

        $totalPlatformFees = $platformFee * $quantity;

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
     * Obtenir un résumé formaté pour affichage.
     */
    public static function getFormattedSummary($event, $price, $quantity = 1)
    {
        $calc = self::calculateForEvent($event, $price, $quantity);

        return [
            'price_display' => CurrencyHelper::formatFCFA($calc['gross_amount']),
            'promoter_earnings' => CurrencyHelper::formatFCFA($calc['net_amount']),
            'platform_commission' => CurrencyHelper::formatFCFA($calc['commission_amount']),
            'commission_rate' => $calc['commission_rate'].'%',
            'promoter_percentage' => $calc['promoter_percentage'].'%',
            'platform_percentage' => $calc['platform_percentage'].'%',
            'breakdown' => [
                'gross' => $calc['gross_amount'],
                'net' => $calc['net_amount'],
                'commission' => $calc['commission_amount'],
                'fees' => $calc['total_platform_fees'],
            ],
        ];
    }

    /**
     * Générer le HTML pour l'affichage des commissions.
     */
    public static function generateCommissionWidget($event, $price, $quantity = 1)
    {
        $summary = self::getFormattedSummary($event, $price, $quantity);

        return view('components.commission-widget', [
            'summary' => $summary,
            'event' => $event,
            'price' => $price,
            'quantity' => $quantity,
        ])->render();
    }

    /**
     * Calculer les gains estimés pour un promoteur sur une période.
     */
    public static function estimateMonthlyEarnings($promoterId, $averageEventPrice = 5000, $estimatedTicketsPerEvent = 50, $eventsPerMonth = 2)
    {
        $dummyEvent = new Event(['promoter_id' => $promoterId]);
        $calc = self::calculateForEvent($dummyEvent, $averageEventPrice, $estimatedTicketsPerEvent);

        $earningsPerEvent = $calc['net_amount'];
        $monthlyEarnings = $earningsPerEvent * $eventsPerMonth;

        return [
            'per_event' => CurrencyHelper::formatFCFA($earningsPerEvent),
            'per_month' => CurrencyHelper::formatFCFA($monthlyEarnings),
            'commission_rate' => $calc['commission_rate'].'%',
            'breakdown' => [
                'revenue_per_event' => CurrencyHelper::formatFCFA($averageEventPrice * $estimatedTicketsPerEvent),
                'tickets_per_event' => $estimatedTicketsPerEvent,
                'events_per_month' => $eventsPerMonth,
                'net_per_event' => $earningsPerEvent,
                'net_per_month' => $monthlyEarnings,
            ],
        ];
    }
}
