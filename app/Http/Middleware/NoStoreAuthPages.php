<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Les pages à formulaire d'authentification contiennent un jeton CSRF lié à la session : un navigateur qui les
 * restaure depuis son cache (retour arrière, onglet ré-ouvert) renverrait un vieux jeton et provoquerait un 419.
 */
class NoStoreAuthPages
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethod('GET') && $request->is('login', 'register', 'password/*')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }
}
