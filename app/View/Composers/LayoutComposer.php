<?php
// app/View/Composers/LayoutComposer.php

namespace App\View\Composers;

use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class LayoutComposer
{
    /**
     * Détermine automatiquement le layout à utiliser selon le rôle
     */
    public function compose(View $view)
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Définir le layout selon le rôle
            $layout = match($user->role) {
                'admin' => 'layouts.admin',
                'promoteur' => 'layouts.promoteur', 
                'acheteur' => 'layouts.acheteur',
                default => 'layouts.app'
            };
            
            $view->with('defaultLayout', $layout);
        } else {
            $view->with('defaultLayout', 'layouts.app');
        }
    }
}
