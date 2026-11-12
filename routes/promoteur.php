<?php

use App\Http\Controllers\Promoteur\EventController;
use App\Http\Controllers\Promoteur\ProfileController;
use App\Http\Controllers\Promoteur\PromoteurController;
use App\Http\Controllers\Promoteur\ReportsController;
use App\Http\Controllers\Promoteur\SalesController;
use App\Http\Controllers\Promoteur\ScannerController;
use App\Http\Controllers\Promoteur\TicketTypeController;
use App\Http\Controllers\TicketVerificationController;
use Illuminate\Support\Facades\Route;

/*
| Espace promoteur — chargé depuis web.php avec :
|   middleware auth + promoteur, préfixe /promoteur, noms promoteur.*
*/

Route::get('dashboard', [PromoteurController::class, 'dashboard'])->name('dashboard');

// Événements : ressource complète + publication
Route::post('events/{event}/publish', [EventController::class, 'publish'])->name('events.publish');
Route::post('events/{event}/unpublish', [EventController::class, 'unpublish'])->name('events.unpublish');
Route::post('events/{event}/duplicate', [EventController::class, 'duplicate'])->name('events.duplicate');
Route::resource('events', EventController::class);

// Types de billets : ressource imbriquée /events/{event}/tickets/{ticketType}
Route::patch('events/{event}/tickets/{ticketType}/toggle', [TicketTypeController::class, 'toggle'])
    ->name('events.tickets.toggle');
Route::resource('events.tickets', TicketTypeController::class)
    ->except(['show'])
    ->parameters(['tickets' => 'ticketType']);

// Scanner QR
Route::controller(ScannerController::class)->prefix('scanner')->name('scanner.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('verify', 'verify')->name('verify');
    Route::get('stats', 'stats')->name('stats');
    Route::get('recent', 'recent')->name('recent');
    Route::get('search', 'search')->name('search');
});
Route::post('scanner/verify/{ticketCode}', [TicketVerificationController::class, 'authenticatedVerify'])
    ->name('tickets.authenticated.verify');
Route::post('api/authenticated-scan', [TicketVerificationController::class, 'authenticatedScan'])
    ->name('api.authenticated-scan');

// Ventes : promoteur.sales.index
Route::resource('sales', SalesController::class)->only(['index']);

// Rapports : promoteur.reports.{index,events,financial,export}
Route::controller(ReportsController::class)->prefix('reports')->name('reports.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('events', 'index')->defaults('type', 'events')->name('events');
    Route::get('financial', 'index')->defaults('type', 'financial')->name('financial');
    Route::get('export', 'export')->name('export');
});

// Profil (ressource singleton) : promoteur.profile.show / promoteur.profile.update
Route::singleton('profile', ProfileController::class)->only(['show', 'update']);
