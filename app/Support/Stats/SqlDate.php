<?php

namespace App\Support\Stats;

/**
 * Expressions SQL de regroupement par jour / mois, identiques sur SQLite, MySQL et TiDB : les timestamps
 * sont stockés « AAAA-MM-JJ HH:MM:SS » dans le fuseau de l'application, SUBSTR en extrait le préfixe.
 * Aucune fonction propre à un SGBD (pas de DATE_FORMAT / HOUR / strftime), donc aucun aiguillage par driver.
 */
final class SqlDate
{
    /** Date de référence d'une vente : paid_at, à défaut created_at. */
    public const SALE_REF = 'COALESCE(orders.paid_at, orders.created_at)';

    public static function day(string $expression): string
    {
        return "SUBSTR({$expression}, 1, 10)";
    }

    public static function month(string $expression): string
    {
        return "SUBSTR({$expression}, 1, 7)";
    }
}
