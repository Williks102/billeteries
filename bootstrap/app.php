<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Enregistrer les alias de middlewares
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'promoteur' => \App\Http\Middleware\PromoteurMiddleware::class,
            'acheteur' => \App\Http\Middleware\AcheteurMiddleware::class,
            'execution.time' => \App\Http\Middleware\SetExecutionTime::class,
            'layout' => \App\Http\Middleware\SetLayoutMiddleware::class,
            'role' => \App\Http\Middleware\CheckUserRole::class,
        ]);
        
        $middleware->append(\App\Http\Middleware\SetExecutionTime::class);
        $middleware->append(\App\Http\Middleware\SetLayoutMiddleware::class);
        
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // N2 : proxys de confiance pilotés par l'environnement. DÉFAUT = aucun (comportement inchangé).
        // Avec `php artisan config:cache`, .env n'est plus lu à l'exécution : définir ces variables
        // dans l'environnement réel du serveur.
        $middleware->trustProxies(
            at: \App\Support\TrustedProxyConfig::proxies(env('TRUSTED_PROXIES')),
            headers: \App\Support\TrustedProxyConfig::headers(env('TRUSTED_PROXY_HEADERS')),
        );
        
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
