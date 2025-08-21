<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketVerificationController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\PageController;

// Controllers spécialisés par rôle
use App\Http\Controllers\Acheteur\AcheteurController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;

use App\Http\Controllers\Promoteur\PromoteurController;
use App\Http\Controllers\Promoteur\EventController as PromoteurEventController;
use App\Http\Controllers\Promoteur\TicketTypeController;
use App\Http\Controllers\Promoteur\ScannerController;
use App\Http\Controllers\Promoteur\SalesController;

/*
|--------------------------------------------------------------------------
| Routes Web - MIGRATION COMPLÈTE VERS CONTRÔLEURS SPÉCIALISÉS
|--------------------------------------------------------------------------
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
Route::post('/api/scan-ticket', [TicketVerificationController::class, 'scan'])->name('api.tickets.scan');

// ==================== ROUTES AUTHENTIFIÉES ====================

// Dashboard principal (redirecteur)
Route::middleware(['auth'])->get('/dashboard', function() {
    $user = auth()->user();
    
    return match($user->role) {
        'admin' => redirect()->route('admin.dashboard'),
        'promoteur' => redirect()->route('promoteur.dashboard'),
        'acheteur' => redirect()->route('acheteur.dashboard'),
        default => redirect()->route('home')->with('error', 'Rôle utilisateur non reconnu')
    };
})->name('dashboard');

Route::middleware(['auth'])->group(function () {
    
    // ==================== CHECKOUT ====================
    Route::prefix('checkout')->name('checkout.')->group(function () {
        Route::get('/', [CheckoutController::class, 'show'])->name('show');
        Route::post('/process', [CheckoutController::class, 'process'])->name('process');
        Route::post('/direct', [CheckoutController::class, 'direct'])->name('direct');
        Route::get('/confirmation/{order}', [CheckoutController::class, 'confirmation'])->name('confirmation');
    });
    
    // ==================== TICKETS GÉNÉRAUX ====================
    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/{ticket}', [TicketController::class, 'show'])->name('show');
        Route::get('/{ticket}/download', [TicketController::class, 'download'])->name('download');
    });
    
    // ==================== ESPACE ACHETEUR ====================
    Route::middleware(['acheteur'])->prefix('acheteur')->name('acheteur.')->group(function () {
        Route::get('/dashboard', [AcheteurController::class, 'dashboard'])->name('dashboard');
        
        // Tickets de l'acheteur
        Route::get('/tickets', [AcheteurController::class, 'myTickets'])->name('tickets');
        Route::get('/ticket/{ticket}', [AcheteurController::class, 'showTicket'])->name('ticket.show');
        
        // Commandes de l'acheteur
        Route::get('/orders', [AcheteurController::class, 'orders'])->name('orders');
        Route::get('/order/{order}', [AcheteurController::class, 'orderDetail'])->name('order.detail');
        Route::get('/order/{order}/download', [AcheteurController::class, 'downloadTickets'])->name('order.download');
        Route::get('/orders/{order}/qr-codes', [AcheteurController::class, 'getOrderQRCodes'])->name('orders.qr-codes');
        
        // Profil et favoris
        Route::get('/profile', [AcheteurController::class, 'profile'])->name('profile');
        Route::patch('/profile', [AcheteurController::class, 'updateProfile'])->name('profile.update');
        Route::get('/favorites', [AcheteurController::class, 'favorites'])->name('favorites');
        Route::post('/favorites/{event}', [AcheteurController::class, 'addToFavorites'])->name('favorites.add');
        Route::delete('/favorites/{event}', [AcheteurController::class, 'removeFromFavorites'])->name('favorites.remove');
    });
    
    // ==================== ESPACE PROMOTEUR ====================
    Route::middleware(['promoteur'])->prefix('promoteur')->name('promoteur.')->group(function () {
        Route::get('/dashboard', [PromoteurController::class, 'dashboard'])->name('dashboard');
        
        // Gestion des événements (RESOURCE)
        Route::resource('events', PromoteurEventController::class);
        Route::post('/events/{event}/publish', [PromoteurEventController::class, 'publish'])->name('events.publish');
        Route::post('/events/{event}/unpublish', [PromoteurEventController::class, 'unpublish'])->name('events.unpublish');
        
        // Gestion des types de tickets (NESTED RESOURCE)
        Route::prefix('events/{event}')->name('events.')->group(function () {
            Route::resource('tickets', TicketTypeController::class)->except(['show']);
            Route::patch('/tickets/{ticket}/toggle', [TicketTypeController::class, 'toggle'])->name('tickets.toggle');
        });
        
        // Scanner QR
        Route::prefix('scanner')->name('scanner.')->group(function () {
            Route::get('/', [ScannerController::class, 'index'])->name('index');
            Route::post('/verify', [ScannerController::class, 'verify'])->name('verify');
            Route::get('/stats', [ScannerController::class, 'stats'])->name('stats');
            Route::get('/recent', [ScannerController::class, 'recent'])->name('recent');
            Route::get('/search', [ScannerController::class, 'search'])->name('search');
        });
        
        // Ventes et commissions
        Route::get('/sales', [SalesController::class, 'index'])->name('sales');
        Route::get('/reports', [SalesController::class, 'reports'])->name('reports');
        Route::get('/reports/export', [SalesController::class, 'export'])->name('reports.export');
        
        // Profil promoteur
        Route::get('/profile', [PromoteurController::class, 'profile'])->name('profile');
        Route::patch('/profile', [PromoteurController::class, 'updateProfile'])->name('profile.update');
    });
    // ==================== ESPACE ADMIN - CONTRÔLEURS SPÉCIALISÉS ====================
Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
    
    // ===== DASHBOARD ET FONCTIONS GÉNÉRALES (AdminController) =====
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/commissions', [AdminController::class, 'commissions'])->name('commissions');
    Route::get('/revenues', [AdminController::class, 'revenues'])->name('revenues');
    Route::get('/analytics', [AdminController::class, 'analytics'])->name('analytics');
    Route::get('/profile', [AdminController::class, 'profile'])->name('profile');
    Route::patch('/profile', [AdminController::class, 'updateProfile'])->name('profile.update');
    
    // ===== GESTION DES UTILISATEURS (UserController spécialisé) =====
    Route::resource('users', AdminUserController::class);
    Route::patch('/users/{user}/toggle-email', [AdminUserController::class, 'toggleEmailVerification'])->name('users.toggle-email');
    Route::post('/users/bulk-action', [AdminUserController::class, 'bulkAction'])->name('users.bulk-action');
    Route::get('/users-export', [AdminUserController::class, 'export'])->name('users.export');
    
    // ===== GESTION DES ÉVÉNEMENTS (EventController spécialisé) =====
    Route::resource('events', AdminEventController::class);
    Route::patch('/events/{event}/status', [AdminEventController::class, 'updateStatus'])->name('events.update-status');
    Route::post('/events/bulk-action', [AdminEventController::class, 'bulkAction'])->name('events.bulk-action');
    Route::get('/events-export', [AdminEventController::class, 'export'])->name('events.export');
    
    // ===== GESTION DES COMMANDES (OrderController spécialisé) =====
    Route::resource('orders', AdminOrderController::class)->only(['index', 'show', 'edit', 'update']);
    Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::post('/orders/{order}/refund', [AdminOrderController::class, 'refund'])->name('orders.refund');
    Route::post('/orders/{order}/resend-email', [AdminOrderController::class, 'resendEmail'])->name('orders.resend-email');
    Route::post('/orders/bulk-action', [AdminOrderController::class, 'bulkAction'])->name('orders.bulk-action');
    Route::get('/orders-export', [AdminOrderController::class, 'export'])->name('orders.export');
    
    // ===== GESTION DES TICKETS (TicketController spécialisé) =====
    Route::resource('tickets', AdminTicketController::class)->only(['index', 'show', 'edit', 'update']);
    Route::patch('/tickets/{ticket}/mark-used', [AdminTicketController::class, 'markUsed'])->name('tickets.mark-used');
    Route::patch('/tickets/{ticket}/cancel', [AdminTicketController::class, 'cancel'])->name('tickets.cancel');
    Route::patch('/tickets/{ticket}/reactivate', [AdminTicketController::class, 'reactivate'])->name('tickets.reactivate');
    Route::get('/tickets/{ticket}/download', [AdminTicketController::class, 'download'])->name('tickets.download');
    Route::post('/tickets/bulk-action', [AdminTicketController::class, 'bulkAction'])->name('tickets.bulk-action');
    Route::get('/tickets-export', [AdminTicketController::class, 'export'])->name('tickets.export');
    
    // ===== GESTION DES CATÉGORIES (CategoryController spécialisé) =====
    Route::resource('categories', AdminCategoryController::class);
    Route::patch('/categories/{category}/toggle-status', [AdminCategoryController::class, 'toggleStatus'])->name('categories.toggle-status');
    Route::post('/categories/reorder', [AdminCategoryController::class, 'reorder'])->name('categories.reorder');
    Route::post('/categories/{category}/duplicate', [AdminCategoryController::class, 'duplicate'])->name('categories.duplicate');
    Route::post('/categories/{category}/merge', [AdminCategoryController::class, 'merge'])->name('categories.merge');
    Route::get('/categories-export', [AdminCategoryController::class, 'export'])->name('categories.export');
    
    // ===== GESTION DES PAGES CMS (PageController spécialisé) =====
    Route::resource('pages', AdminPageController::class);
    Route::post('/pages/{page}/duplicate', [AdminPageController::class, 'duplicate'])->name('pages.duplicate');
    Route::patch('/pages/{page}/toggle-status', [AdminPageController::class, 'toggleStatus'])->name('pages.toggle-status');
    Route::post('/pages/reorder', [AdminPageController::class, 'reorder'])->name('pages.reorder');
    
    // ===== COMMISSIONS ET FINANCES (AdminController) =====
    Route::prefix('finances')->name('finances.')->group(function () {
        Route::patch('/commissions/{commission}/status', [AdminController::class, 'updateCommissionStatus'])->name('commissions.update-status');
        Route::post('/commissions/bulk-pay', [AdminController::class, 'bulkPayCommissions'])->name('commissions.bulk-pay');
    });
    
    // ===== RAPPORTS ET EXPORTS GLOBAUX (CORRIGÉ) =====
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [AdminController::class, 'reports'])->name('index');
        Route::get('/sales', [AdminController::class, 'salesReport'])->name('sales');
        Route::get('/financial', [AdminController::class, 'financialReport'])->name('financial');
        Route::get('/users', [AdminController::class, 'usersReport'])->name('users');
        Route::get('/events', [AdminController::class, 'eventsReport'])->name('events');
        Route::get('/export/{type}', [AdminController::class, 'export'])->name('export');
    });
    
    // ===== PARAMÈTRES SYSTÈME (SettingsController spécialisé) =====
    Route::resource('settings', AdminSettingsController::class)->only(['index', 'store']);
    Route::post('/settings/test-email', [AdminSettingsController::class, 'testEmail'])->name('settings.test-email');
    Route::post('/settings/backup', [AdminSettingsController::class, 'backup'])->name('settings.backup');
    Route::post('/settings/clear-cache', [AdminSettingsController::class, 'clearCache'])->name('settings.clear-cache');
    Route::post('/settings/optimize-db', [AdminSettingsController::class, 'optimizeDatabase'])->name('settings.optimize-db');
    Route::post('/settings/cleanup-files', [AdminSettingsController::class, 'cleanupFiles'])->name('settings.cleanup-files');
    
    // ===== EMAILS ET NOTIFICATIONS (AdminController) =====
    Route::prefix('emails')->name('emails.')->group(function () {
        Route::get('/', [AdminController::class, 'emailDashboard'])->name('dashboard');
        Route::post('/test', [AdminController::class, 'testEmail'])->name('test');
        Route::get('/templates', [AdminController::class, 'emailTemplates'])->name('templates');
        Route::post('/orders/{order}/resend', [AdminController::class, 'resendOrderEmail'])->name('resend');
    });
    
    // ===== ROUTES DE COMPATIBILITÉ (redirections vers nouvelles routes) =====
    Route::get('/users-legacy', function() {
        return redirect()->route('admin.users.index');
    })->name('users'); // Ancienne route 'admin.users'
    
    Route::get('/events-legacy', function() {
        return redirect()->route('admin.events.index');
    })->name('events'); // Ancienne route 'admin.events'
    
    Route::get('/orders-legacy', function() {
        return redirect()->route('admin.orders.index');
    })->name('orders'); // Ancienne route 'admin.orders'
    
    Route::get('/tickets-legacy', function() {
        return redirect()->route('admin.tickets.index');
    })->name('tickets'); // Ancienne route 'admin.tickets'
    
    // Autres routes de compatibilité
    Route::get('/commissions/pending', function () { 
        return redirect()->route('admin.commissions', ['status' => 'pending']); 
    })->name('commissions.pending');
    
    Route::get('/events/no-sales', function () { 
        return redirect()->route('admin.events.index', ['filter' => 'no-sales']); 
    })->name('events.no-sales');
    
    Route::get('/promoters/inactive', function () { 
        return redirect()->route('admin.users.index', ['role' => 'promoteur', 'status' => 'inactive']); 
    })->name('promoters.inactive');
    
    Route::get('/promoters/{user}', function ($user) { 
        return redirect()->route('admin.users.show', $user);
    })->name('promoters.show');
});
    // Profil générique (fallback)
    Route::get('/profile', function () {
        $user = auth()->user();
        
        return match($user->role) {
            'acheteur' => redirect()->route('acheteur.profile'),
            'promoteur' => redirect()->route('promoteur.profile'),
            'admin' => redirect()->route('admin.profile'),
            default => view('profile', compact('user'))
        };
    })->name('profile');
});

// ==================== ROUTES DE DEBUG (RETIRER EN PRODUCTION) ====================
if (app()->environment(['local', 'staging'])) {
    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/test-qr-methods', function() {
            $service = app(\App\Services\QRCodeService::class);
            return $service->testAllMethods();
        });
        
        Route::get('/test-qr-diagnostic', [HomeController::class, 'testQR']);
        
        Route::get('/test-ticket-simple', function() {
            $ticket = App\Models\Ticket::where('ticket_code', 'TKT-NYUEWEI5')->first();
            
            return [
                'ticket_code' => $ticket->ticket_code ?? 'Non trouvé',
                'status' => $ticket->status ?? 'N/A',
                'has_order_item' => $ticket->order_item_id ? 'Oui' : 'Non',
            ];
        });
    });
}