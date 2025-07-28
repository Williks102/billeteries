<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class SetLayoutMiddleware
{
    public function handle(Request $request, Closure $next, $layout = null)
    {
        if ($layout) {
            View::share('currentLayout', "layouts.{$layout}");
        }
        
        // Détection automatique basée sur l'URL
        $user = auth()->user();
        
        if ($request->is('admin/*') && $user && $user->isAdmin()) {
            View::share('currentLayout', 'layouts.admin');
        } elseif ($request->is('promoteur/*') && $user && $user->isPromoteur()) {
            View::share('currentLayout', 'layouts.promoteur');
        } elseif ($request->is('acheteur/*') && $user && $user->isAcheteur()) {
            View::share('currentLayout', 'layouts.acheteur');
        } else {
            View::share('currentLayout', 'layouts.app');
        }
        
        return $next($request);
    }
}