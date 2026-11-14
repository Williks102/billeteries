<?php

namespace App\Support;

/**
 * Politique de sécurité du contenu (CSP) : nonce par requête et politique cible « strict » (mode rapport seulement).
 *
 * Le nonce vit dans un attribut de la requête (jamais en session, ni en cache) : un nonce par requête, jamais réutilisé.
 */
final class Csp
{
    public const REQUEST_ATTRIBUTE = 'csp_nonce';

    /** Directives communes à la politique appliquée et à la politique de rapport (une seule source). */
    public const FONT_SRC = "'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net";

    public const CONNECT_SRC = "'self' https://cdn.jsdelivr.net";

    /** 18 octets aléatoires cryptographiques -> 24 caractères base64, sans remplissage « = ». */
    public static function generateNonce(): string
    {
        return base64_encode(random_bytes(18));
    }

    /** Nonce de la requête en cours, ou chaîne vide s'il n'y en a pas. */
    public static function nonce(): string
    {
        try {
            $value = app('request')->attributes->get(self::REQUEST_ATTRIBUTE);
        } catch (\Throwable) {
            return '';
        }

        return is_string($value) && preg_match('/^[A-Za-z0-9+\/=]+$/', $value) === 1 ? $value : '';
    }

    /** Fragment d'attribut pour la directive Blade `@cspNonce` : `nonce="…"`, ou rien sans nonce. */
    public static function attribute(): string
    {
        $nonce = self::nonce();

        return $nonce === '' ? '' : 'nonce="'.$nonce.'"';
    }

    /**
     * Politique cible, envoyée en `Content-Security-Policy-Report-Only` : elle ne bloque RIEN, elle rapporte.
     *
     * @param  string  $imgSrc  mêmes sources d'images que la politique appliquée (inclut l'origine du disque média)
     */
    public static function reportOnlyPolicy(string $nonce, string $imgSrc): string
    {
        return "default-src 'self'; ".
            // Les CDN restent listés jusqu'au lot C (jQuery et Chart.js hébergés localement).
            "script-src 'self' 'nonce-{$nonce}' https://code.jquery.com https://cdn.jsdelivr.net; ".
            // Signale les attributs onclick / onsubmit… : c'est ce que mesure le lot B.
            "script-src-attr 'none'; ".
            // Les styles viennent au lot D : 'unsafe-inline' reste admis d'ici là.
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; ".
            "img-src {$imgSrc}; ".
            'font-src '.self::FONT_SRC.'; '.
            'connect-src '.self::CONNECT_SRC.'; '.
            "frame-src 'self'; ".
            "object-src 'none'; ".
            "base-uri 'self'; ".
            "frame-ancestors 'self'; ".
            "form-action 'self'; ".
            'report-uri /csp-report';
    }
}
