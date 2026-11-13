<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Source unique du taux de commission PAR DÉFAUT (en %).
 *
 * Ordre : valeur enregistrée en base (settings.commission.default_rate, 0 à 100) puis, à défaut ou si
 * elle est invalide / illisible, config('payments.default_commission_rate') (DEFAULT_COMMISSION_RATE).
 * Les réglages par promoteur / catégorie (CommissionSetting) priment toujours : ils sont gérés par Order.
 *
 * Aucun cache : une lecture SQL par calcul, donc jamais de valeur périmée entre requêtes ou serveurs.
 * Le taux 0 est une valeur valide (commission nulle).
 */
class CommissionRate
{
    public const KEY = 'commission.default_rate';

    /** Valeur de secours (variable d'environnement). */
    public static function fallback(): float
    {
        return (float) config('payments.default_commission_rate');
    }

    /** Valeur enregistrée en base, ou null si absente / invalide / illisible. Ne lève jamais. */
    public static function stored(): ?float
    {
        try {
            $row = Setting::query()->where('key', self::KEY)->first();
        } catch (\Throwable $e) {
            return null; // table absente, base indisponible : on retombe sur la config
        }

        if (! $row) {
            return null;
        }

        return self::validOrNull($row->value);
    }

    /** Taux par défaut effectif. */
    public static function default(): float
    {
        return self::stored() ?? self::fallback();
    }

    /** 'database' si une valeur valide est en base, sinon 'env'. */
    public static function source(): string
    {
        return self::stored() !== null ? 'database' : 'env';
    }

    /** Enregistre le taux (déjà validé par l'appelant, revérifié ici). */
    public static function save(float $rate): void
    {
        if (self::validOrNull($rate) === null) {
            throw new \InvalidArgumentException('Taux de commission hors de 0 à 100.');
        }

        $setting = Setting::set(self::KEY, number_format($rate, 2, '.', ''), 'decimal');
        $setting->forceFill(['group' => 'payment', 'description' => 'Taux de commission par défaut (%)', 'is_public' => false])->save();
        Cache::forget('setting.'.self::KEY);
    }

    /** Supprime la valeur en base : retour à la valeur de secours du serveur. */
    public static function reset(): void
    {
        Setting::query()->where('key', self::KEY)->delete();
        Cache::forget('setting.'.self::KEY);
    }

    private static function validOrNull(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }
        $f = (float) $value;

        return ($f >= 0 && $f <= 100) ? $f : null;
    }
}
