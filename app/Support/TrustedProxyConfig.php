<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\Request;

/**
 * Interprète les variables d'environnement TRUSTED_PROXIES / TRUSTED_PROXY_HEADERS (N2).
 * Défaut : aucun proxy de confiance (null) ; `*` n'est jamais appliqué sans être demandé explicitement.
 */
class TrustedProxyConfig
{
    /**
     * @return array<int, string>|string|null  null = aucun proxy de confiance ; '*' ; ou liste d'IP/CIDR
     */
    public static function proxies(?string $value): array|string|null
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if ($value === '*') {
            return '*';
        }

        $list = array_values(array_filter(array_map('trim', explode(',', $value)), fn ($v) => $v !== '' && $v !== '*'));

        return $list === [] ? null : $list;
    }

    /**
     * @return int|null  masque Symfony ; null = défaut du framework (X-Forwarded-For/Host/Port/Proto)
     */
    public static function headers(?string $value): ?int
    {
        return match (strtolower(trim((string) $value))) {
            'forwarded' => Request::HEADER_FORWARDED,
            'aws-elb' => Request::HEADER_X_FORWARDED_AWS_ELB,
            default => null,
        };
    }
}
