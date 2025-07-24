<?php

namespace App\Helpers;

class CurrencyHelper
{
    /**
     * Formate un montant en FCFA
     * 
     * @param int $amount Montant en centimes FCFA
     * @return string Montant formaté (ex: "25 000 FCFA")
     */
    public static function formatFCFA($amount)
    {
        if (is_null($amount)) {
            return '0 FCFA';
        }
        
        return number_format($amount, 0, ',', ' ') . ' FCFA';
    }

    /**
     * Parse un montant formaté en FCFA vers un entier
     * 
     * @param string $formatted Montant formaté (ex: "25 000 FCFA")
     * @return int Montant en centimes
     */
    public static function parseFCFA($formatted)
    {
        return (int) str_replace([' ', 'FCFA', ','], '', $formatted);
    }

    /**
     * Formate sans le symbole FCFA
     * 
     * @param int $amount
     * @return string (ex: "25 000")
     */
    public static function formatNumber($amount)
    {
        if (is_null($amount)) {
            return '0';
        }
        
        return number_format($amount, 0, ',', ' ');
    }

    /**
     * Calcule un pourcentage d'un montant
     * 
     * @param int $amount
     * @param float $percentage
     * @return int
     */
    public static function calculatePercentage($amount, $percentage)
    {
        return (int) round($amount * ($percentage / 100));
    }

    /**
     * Ajoute des frais fixes à un montant
     * 
     * @param int $amount
     * @param int $fixedFee
     * @return int
     */
    public static function addFixedFee($amount, $fixedFee)
    {
        return $amount + $fixedFee;
    }

    /**
     * Calcule la commission complète (pourcentage + frais fixes)
     * 
     * @param int $amount Montant de base
     * @param float $rate Taux de commission en %
     * @param int $fixedFee Frais fixes
     * @return array ['commission' => int, 'net' => int]
     */
    public static function calculateCommission($amount, $rate, $fixedFee = 0)
    {
        $commission = self::calculatePercentage($amount, $rate) + $fixedFee;
        $net = $amount - $commission;
        
        return [
            'commission' => $commission,
            'net' => max(0, $net) // Éviter les montants négatifs
        ];
    }
}