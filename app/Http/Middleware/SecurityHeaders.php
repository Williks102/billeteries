<?php

namespace App\Http\Middleware;

use App\Support\Csp;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Nonce CSP : un par requête, généré AVANT le rendu des vues (elles l'affichent via @cspNonce).
        $nonce = Csp::generateNonce();
        $request->attributes->set(Csp::REQUEST_ATTRIBUTE, $nonce);

        $response = $next($request);

        // Images hébergées hors du site (R2, CDN) : le domaine du disque média doit être autorisé dans img-src.
        $mediaOrigin = \App\Support\Media::publicOrigin();
        $imgSrc = "'self' data: blob: https://res.cloudinary.com".($mediaOrigin ? ' '.$mediaOrigin : '');

                        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' https://code.jquery.com https://cdn.jsdelivr.net; " . 
               "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; " .
               "img-src ".$imgSrc."; " .
               // Le bip du scanner de billets est un son en URI data: ; sans media-src, default-src 'self' le bloquait déjà
               // (constaté par le rapport CSP) : on l'autorise explicitement, sans autre élargissement.
               "media-src 'self' data:; " .
               "font-src ".Csp::FONT_SRC."; " .
               "connect-src ".Csp::CONNECT_SRC."; " .
               "frame-src 'self'; " .
               "object-src 'none'; " .
               "base-uri 'self'; " .
               "frame-ancestors 'self'; " .
               // Paiement manuel : plus aucune redirection vers un fournisseur externe
               "form-action 'self'; ".

               "upgrade-insecure-requests;";




        $response->headers->set('Content-Security-Policy', $csp);
        // Politique stricte cible, en rapport seulement (ne bloque rien) ; coexiste avec la politique appliquée.
        if (config('security.csp_report_only', true)) {
            $response->headers->set('Content-Security-Policy-Report-Only', Csp::reportOnlyPolicy($nonce, $imgSrc));
        }
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        // camera=(self) : le scanner de billets du promoteur utilise la caméra du navigateur
        $response->headers->set('Permissions-Policy', 'camera=(self), microphone=(), geolocation=(), payment=(), usb=()');

        return $response;
    }
}
