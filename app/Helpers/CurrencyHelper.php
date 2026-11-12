<?php

namespace App\Helpers;

class CurrencyHelper
{
    /** Espace insécable (U+00A0) : « 10 000 FCFA » ne se coupe jamais en fin de ligne. */
    public const NBSP = "\u{00A0}";

    /**
     * Formate un montant en FCFA
     * 
     * @param int $amount Montant en centimes FCFA
     * @return string Montant formaté avec espaces insécables (ex: "25 000 FCFA")
     */
    public static function formatFCFA($amount)
    {
        if (is_null($amount)) {
            return '0' . self::NBSP . 'FCFA';
        }
        
        return self::formatNumber($amount) . self::NBSP . 'FCFA';
    }

    /**
     * Prix affiché au public : « Gratuit » pour 0, sinon le montant FCFA. Affichage seul, aucun calcul.
     */
    public static function formatPrice($amount)
    {
        return (int) $amount === 0 ? 'Gratuit' : self::formatFCFA($amount);
    }

    /**
     * Parse un montant formaté en FCFA vers un entier
     * 
     * @param string $formatted Montant formaté (ex: "25 000 FCFA")
     * @return int Montant en centimes
     */
    public static function parseFCFA($formatted)
    {
        return (int) str_replace([' ', self::NBSP, "\u{202F}", 'FCFA', ','], '', $formatted);
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
        
        return number_format((float) $amount, 0, ',', self::NBSP);
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