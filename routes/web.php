<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\EmailAvailabilityController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\GuestCheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketVerificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes web
|--------------------------------------------------------------------------
| Organisation :
|   - web.php        : routes publiques + routes authentifiées communes
|   - acheteur.php   : espace acheteur   (préfixe /acheteur,  noms acheteur.*)
|   - promoteur.php  : espace promoteur  (préfixe /promoteur, noms promoteur.*)
|   - admin.php      : espace admin      (préfixe /admin,     noms admin.*)
|
| Convention : Route::resource / Route::singleton pour le CRUD, routes
| supplémentaires déclarées AVANT la ressource quand leur URI peut être
| capturée par `{param}`. Aucune closure métier dans les routes.
*/

// ==================== ROUTES PUBLIQUES ====================

// Page d'accueil et événements
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/events', [HomeController::class, 'allEvents'])->name('events.all');
Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
Route::get('/search', [HomeController::class, 'search'])->name('search');
Route::get('/api/events', [HomeController::class, 'getEvents'])->name('api.events');

// Catégories d'événements
Route::get('/categories/{category}', [HomeController::class, 'category'])->name('categories.show');

// Pages dynamiques (CMS)
Route::get('/page/{slug}', [PageController::class, 'showCMS'])->name('pages.cms');

// Pages statiques
Route::get('/how-it-works', [PageController::class, 'howItWorks'])->name('pages.how-it-works');
Route::get('/faq', [PageController::class, 'faq'])->name('pages.faq');
Route::get('/contact', [PageController::class, 'contact'])->name('pages.contact');
Route::post('/contact', [PageController::class, 'submitContact'])->name('pages.contact.submit');
Route::get('/terms-of-service', [PageController::class, 'termsOfService'])->name('pages.terms');
Route::get('/privacy-policy', [PageController::class, 'privacyPolicy'])->name('pages.privacy');
Route::get('/legal-mentions', [PageController::class, 'legalMentions'])->name('pages.legal');
Route::get('/promoter-guide', [PageController::class, 'promoterGuide'])->name('pages.promoter-guide');
Route::get('/pricing', [PageController::class, 'pricing'])->name('pages.pricing');
Route::get('/support', [PageController::class, 'support'])->name('pages.support');
Route::post('/support', [PageController::class, 'submitSupport'])->name('pages.support.submit');

// Authentification
Auth::routes();

// ==================== PANIER (PUBLIQUE) ====================
Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'show'])->name('show');
    Route::post('/add', [CartController::class, 'add'])->name('add');
    Route::patch('/update', [CartController::class, 'update'])->name('update');
    Route::delete('/remove', [CartController::class, 'remove'])->name('remove');
    Route::delete('/clear', [CartController::class, 'clear'])->name('clear');
    Route::get('/data', [CartController::class, 'getCartData'])->name('data');
    Route::post('/extend-timer', [CartController::class, 'extendTimer'])->name('extend.timer');
    Route::get('/status', [CartController::class, 'getStatus'])->name('status');
});

// ==================== VÉRIFICATION TICKETS (PUBLIQUE) ====================
Route::get('/verify-ticket/{ticketCode}', [TicketVerificationController::class, 'verify'])->name('tickets.verify');
Route::get('/api/verify-ticket/{ticketCode}', [TicketVerificationController::class, 'verifyApi'])->name('api.tickets.verify');
// Route::post('/api/scan-ticket', [TicketVerificationController::class, 'scan'])->name('api.tickets.scan');

// ==================== ROUTES AUTHENTIFIÉES ====================

// Dashboard principal (redirecteur)
Route::middleware(['auth'])->get('/dashboard', function () {
    $user = auth()->user();

    return match ($user->role) {
        'admin' => redirect()->route('admin.dashboard'),
        'promoteur' => redirect()->route('promoteur.dashboard'),
        'acheteur' => redirect()->route('acheteur.dashboard'),
        default => redirect()->route('home')->with('error', 'Rôle utilisateur non reconnu')
    };
})->name('dashboard');

Route::prefix('checkout')->name('checkout.')->group(function () {

    // Checkout classique (nécessite auth)
    Route::middleware(['auth'])->group(function () {
        Route::get('/', [CheckoutController::class, 'show'])->name('show');
        Route::post('/process', [CheckoutController::class, 'process'])->name('process');
        Route::post('/direct', [CheckoutController::class, 'direct'])->name('direct');
        Route::get('/confirmation/{order}', [CheckoutController::class, 'confirmation'])->name('confirmation');
    });

    // Checkout invité (sans auth)
    Route::prefix('guest')->name('guest.')->group(function () {
        Route::get('/', [GuestCheckoutController::class, 'show'])->name('show');
        Route::post('/process', [GuestCheckoutController::class, 'process'])->name('process'); // 🆕 Ici aussi
        Route::get('/confirmation/{token}', [GuestCheckoutController::class, 'confirmation'])->name('confirmation');
        Route::post('/create-account/{token}', [GuestCheckoutController::class, 'createAccountAfterPurchase'])->name('create-account');
    });

    // Redirection intelligente depuis le panier
    Route::get('/choose', function () {
        if (auth()->check()) {
            return redirect()->route('checkout.show');
        }

        return redirect()->route('checkout.guest.show');
    })->name('choose');
});

Route::post('/api/check-email', EmailAvailabilityController::class)
    ->middleware('throttle:60,1')
    ->name('api.check-email');

// ==================== ROUTES AUTHENTIFIÉES ====================
Route::middleware(['auth'])->group(function () {

    // Billets (consultation / téléchargement selon le rôle)
    Route::resource('tickets', TicketController::class)->only(['show']);
    Route::get('tickets/{ticket}/download', [TicketController::class, 'download'])->name('tickets.download');

    // Espaces par rôle
    Route::middleware('acheteur')->prefix('acheteur')->name('acheteur.')
        ->group(base_path('routes/acheteur.php'));

    Route::middleware('promoteur')->prefix('promoteur')->name('promoteur.')
        ->group(base_path('routes/promoteur.php'));

    Route::middleware('admin')->prefix('admin')->name('admin.')
        ->group(base_path('routes/admin.php'));

    // Profil générique : redirige vers le profil du rôle
    Route::get('/profile', function () {
        $user = auth()->user();

        return match ($user->role) {
            'acheteur' => redirect()->route('acheteur.profile.show'),
            'promoteur' => redirect()->route('promoteur.profile.show'),
            'admin' => redirect()->route('admin.profile'),
            default => view('profile', compact('user')),
        };
    })->name('profile');
});

// ==================== OUTILS DE DEBUG (local / staging uniquement) ====================
if (app()->environment(['local', 'staging'])) {
    Route::middleware(['auth', 'admin'])->prefix('debug')->group(function () {
        Route::get('/qr-methods', fn () => app(\App\Services\QRCodeService::class)->testAllMethods());
        Route::get('/qr-diagnostic', [HomeController::class, 'testQR']);
    });
}
