<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CspReportController;
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

// Rapports CSP envoyés par le navigateur (public, sans CSRF, 204 toujours). Pas de session ni de cookie :
// un rapport anonyme ne doit pas créer de ligne de session. Débit : 30/min par IP (+ plafond global dans le service).
Route::post('/csp-report', [CspReportController::class, 'store'])
    ->middleware('throttle:30,1,csp-report')
    ->withoutMiddleware([
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        // (le jeton CSRF est déjà exempté dans bootstrap/app.php ; ce middleware ajouterait sinon un cookie XSRF, donc une session)
        \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ])
    ->name('csp.report');

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
});

// ==================== VÉRIFICATION TICKETS (PUBLIQUE) ====================
// S-6/S-7 : le code du billet est le secret porteur ; throttle par IP (clé commune aux deux routes) contre l'énumération.
Route::middleware('throttle:' . config('payments.verify_ticket_throttle_per_minute', 60) . ',1,verify-ticket')->group(function () {
    Route::get('/verify-ticket/{ticketCode}', [TicketVerificationController::class, 'verify'])->name('tickets.verify');
    Route::get('/api/verify-ticket/{ticketCode}', [TicketVerificationController::class, 'verifyApi'])->name('api.tickets.verify');
});
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
        Route::post('/process', [CheckoutController::class, 'process'])
            ->middleware('throttle:' . config('payments.checkout_throttle_per_minute', 10) . ',1,checkout')
            ->name('process');
        Route::get('/confirmation/{order}', [CheckoutController::class, 'confirmation'])->name('confirmation');
    });

    // Réservation directe depuis la fiche événement (connecté ou invité) : saute la page Panier
    Route::post('/direct', [CheckoutController::class, 'direct'])
        ->middleware('throttle:' . config('payments.checkout_throttle_per_minute', 10) . ',1,checkout')
        ->name('direct');

    // Checkout invité (sans auth)
    Route::prefix('guest')->name('guest.')->group(function () {
        Route::get('/', [GuestCheckoutController::class, 'show'])->name('show');
        Route::post('/process', [GuestCheckoutController::class, 'process'])
            ->middleware('throttle:' . config('payments.checkout_throttle_per_minute', 10) . ',1,checkout')
            ->name('process');
        Route::get('/confirmation/{token}', [GuestCheckoutController::class, 'confirmation'])->name('confirmation');
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
