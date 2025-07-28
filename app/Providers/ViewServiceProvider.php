<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\View\Composers\LayoutComposer;

class ViewServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // Composer pour toutes les vues
        View::composer('*', LayoutComposer::class);
        
        // Ou spécifiquement pour les vues admin
        View::composer(['admin.*', 'layouts.admin'], LayoutComposer::class);
    }
}
