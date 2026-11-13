<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Gate;
use App\Models\Ticket;
use App\Observers\TicketObserver;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Blade;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\QRCodeService::class);
        $loader = \Illuminate\Foundation\AliasLoader::getInstance();
        $loader->alias('Currency', \App\Facades\Currency::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Pilote « r2 » : S3 standard, sans l'en-tête ACL (non pris en charge par Cloudflare R2).
        \Illuminate\Support\Facades\Storage::extend('r2', function ($app, array $config) {
            $disk = $app['filesystem']->createS3Driver($config);
            $disk->getClient()->getHandlerList()->prependSign(
                \Aws\Middleware::mapRequest(
                    fn (\Psr\Http\Message\RequestInterface $request) => $request->withoutHeader('x-amz-acl')
                ),
                'r2-sans-acl'
            );

            return $disk;
        });

        // Pilote « cloudinary » (alternative gratuite sans carte à R2) : adaptateur Flysystem maison, voir docs/stockage-cloudinary.md.
        \Illuminate\Support\Facades\Storage::extend('cloudinary', function ($app, array $config) {
            $settings = \App\Support\Filesystem\CloudinaryConfig::fromDiskConfig($config);
            if ($settings === null) {
                // Aucune valeur dans le message : seulement le nom de la variable à corriger.
                throw new \RuntimeException('Stockage Cloudinary non configuré ('.\App\Support\Filesystem\CloudinaryConfig::problem($config).').');
            }

            $gateway = $app->bound(\App\Support\Filesystem\CloudinaryGateway::class)
                ? $app->make(\App\Support\Filesystem\CloudinaryGateway::class)
                : new \App\Support\Filesystem\SdkCloudinaryGateway($settings);
            $adapter = new \App\Support\Filesystem\CloudinaryAdapter($settings, $gateway);

            return new \Illuminate\Filesystem\FilesystemAdapter(new \League\Flysystem\Filesystem($adapter, $config), $adapter, $config);
        });

        // Mode démo (paiements simulés, désactivé par défaut) : `$demoMode` disponible dans toutes les vues
        // pour le bandeau des layouts.
        if (config('payments.demo_blocked_in_production')) {
            \Illuminate\Support\Facades\Log::critical('DEMO_AUTO_CONFIRM_PAYMENTS=true ignoré en production : ajoutez DEMO_MODE_ACKNOWLEDGED=true pour une démo volontaire.');
        }
        View::share('demoMode', (bool) config('payments.demo_auto_confirm', false));

        // Résoudre le problème de longueur d'index MySQL
        Schema::defaultStringLength(191);
        
        // Gates pour les rôles (seulement si utilisateur connecté)
        Gate::define('admin-access', function ($user) {
            return $user && $user->isAdmin();
        });
        
        Gate::define('promoteur-access', function ($user) {
            return $user && ($user->isPromoteur() || $user->isAdmin());
        });
        
        Gate::define('acheteur-access', function ($user) {
            return $user && ($user->isAcheteur() || $user->isAdmin());

        });

           View::composer('*', function ($view) {
        if (auth()->check()) {
            $user = auth()->user();
            $layout = match($user->role) {
                'admin' => 'layouts.admin',
                'promoteur' => 'layouts.promoteur',
                'acheteur' => 'layouts.acheteur',
                default => 'layouts.app'
            };
            $view->with('defaultLayout', $layout);
        }
    });

       Blade::directive('autoLayout', function ($expression) {
        return "<?php 
            \$user = auth()->user();
            \$layout = \$user ? match(\$user->role) {
                'admin' => 'layouts.admin',
                'promoteur' => 'layouts.promoteur', 
                'acheteur' => 'layouts.acheteur',
                default => 'layouts.app'
            } : 'layouts.app';
            echo \"@extends('\" . \$layout . \"')\";
        ?>";
    });
     // Directive pour sidebar conditionnelle
    Blade::directive('adminSidebar', function () {
        return "<?php if(auth()->check() && auth()->user()->isAdmin()): ?>
                    @include('partials.admin-sidebar')
                <?php endif; ?>";
    });
}
}
