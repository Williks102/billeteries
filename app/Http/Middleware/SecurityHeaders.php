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

                        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' https://code.jquery.com https://cdn.jsdelivr.net; " . 
               "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; " .
               "img-src 'self' data: blob: https://res.cloudinary.com; " .
               "font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; " .
               "connect-src 'self' https://cdn.jsdelivr.net; " .
               "frame-src 'self'; " .
               "object-src 'none'; " .
               "base-uri 'self'; " .
               "frame-ancestors 'self'; " .
               // AJOUT : Autorisation de paiementpro.net pour le checkout
               "form-action 'self' https://clicbillet.com https://www.clicbillet.com https://*.paiementpro.net https://paiementpro.net https://mpayment.orange-money.com https://multi.app.orange-money.com https://maxit-link.com https://pay.wave.com https://www.wave.com https://*.confirm.wave.com https://promo.wave.com; ".

               "upgrade-insecure-requests;";




        $response->headers->set('Content-Security-Policy', $csp);
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
