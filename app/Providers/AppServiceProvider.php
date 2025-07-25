<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Gate;
use App\Models\Ticket;
use App\Observers\TicketObserver;

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

        //Ticket::observe(TicketObserver::class);
    }

}