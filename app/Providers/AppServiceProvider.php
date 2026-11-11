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
