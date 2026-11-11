<?php

use App\Http\Controllers\Acheteur\AcheteurController;
use App\Http\Controllers\Acheteur\OrderController;
use App\Http\Controllers\Acheteur\ProfileController;
use App\Http\Controllers\Acheteur\TicketController;
use Illuminate\Support\Facades\Route;

/*
| Espace acheteur — chargé depuis web.php avec :
|   middleware auth + acheteur, préfixe /acheteur, noms acheteur.*
*/

Route::get('dashboard', [AcheteurController::class, 'dashboard'])->name('dashboard');

// Commandes : acheteur.orders.index / acheteur.orders.show (+ actions dédiées)
Route::get('orders/{order}/download', [OrderController::class, 'download'])->name('orders.download');
Route::get('orders/{order}/qr-codes', [OrderController::class, 'qrCodes'])->name('orders.qr-codes');
Route::resource('orders', OrderController::class)->only(['index', 'show']);

// Billets : acheteur.tickets.index / acheteur.tickets.show
Route::resource('tickets', TicketController::class)->only(['index', 'show']);

// Profil (ressource singleton) : acheteur.profile.show / acheteur.profile.update
Route::singleton('profile', ProfileController::class)->only(['show', 'update']);

// Favoris — ATTENTION : les méthodes favorites/addToFavorites/removeFromFavorites
// n'existent pas dans AcheteurController (fonctionnalité non implémentée).
// Routes conservées pour ne pas casser le lien du menu ; à implémenter ou retirer.
Route::get('favorites', [AcheteurController::class, 'favorites'])->name('favorites');
Route::post('favorites/{event}', [AcheteurController::class, 'addToFavorites'])->name('favorites.add');
Route::delete('favorites/{event}', [AcheteurController::class, 'removeFromFavorites'])->name('favorites.remove');
