<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Images hébergées hors du site (R2, CDN) : le domaine du disque média doit être autorisé dans img-src.
        $mediaOrigin = \App\Support\Media::publicOrigin();
        $imgSrc = "'self' data: blob: https://res.cloudinary.com".($mediaOrigin ? ' '.$mediaOrigin : '');

                        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' https://code.jquery.com https://cdn.jsdelivr.net; " . 
               "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; " .
               "img-src ".$imgSrc."; " .
               "font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; " .
               "connect-src 'self' https://cdn.jsdelivr.net; " .
               "frame-src 'self'; " .
               "object-src 'none'; " .
               "base-uri 'self'; " .
               "frame-ancestors 'self'; " .
               // Paiement manuel : plus aucune redirection vers un fournisseur externe
               "form-action 'self'; ".

               "upgrade-insecure-requests;";




        $response->headers->set('Content-Security-Policy', $csp);
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
