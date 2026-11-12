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
        $middleware->append(\App\Http\Middleware\NoStoreAuthPages::class);

        // N2 : proxys de confiance pilotés par l'environnement. DÉFAUT = aucun (comportement inchangé).
        // Avec `php artisan config:cache`, .env n'est plus lu à l'exécution : définir ces variables
        // dans l'environnement réel du serveur.
        $middleware->trustProxies(
            at: \App\Support\TrustedProxyConfig::proxies(env('TRUSTED_PROXIES')),
            headers: \App\Support\TrustedProxyConfig::headers(env('TRUSTED_PROXY_HEADERS')),
        );
        
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // 419 (jeton CSRF invalide : session expirée, cookie absent, vieille page) : au lieu de la page d'erreur,
        // retour au formulaire avec un message clair. Le contenu saisi est conservé, jamais les mots de passe.
        // (Laravel convertit TokenMismatchException en HttpException 419 avant d'appeler ces fonctions.)
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() !== 419 || $request->expectsJson()) {
                return null; // autres erreurs et réponse JSON : comportement par défaut
            }

            $message = 'Votre session a expiré. Rechargez la page puis réessayez ; si le problème continue, vérifiez que les cookies sont autorisés.';
            $target = $request->routeIs('logout') ? route('login') : (url()->previous() ?: route('login'));

            return redirect($target)
                ->withInput($request->except(['_token', 'password', 'password_confirmation', 'current_password', 'new_password', 'new_password_confirmation']))
                ->withErrors(['session' => $message]);
        });
    })->create();
