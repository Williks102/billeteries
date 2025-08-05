<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketVerificationController;
use App\Http\Controllers\Acheteur\AcheteurController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Promoteur\PromoteurController;
use App\Http\Controllers\Promoteur\TicketTypeController;

/*
|--------------------------------------------------------------------------
| Web Routes - Version basée sur votre projet réel
|--------------------------------------------------------------------------
*/

// ==================== ROUTES PUBLIQUES ====================

// Page d'accueil et événements (SANS AUTHENTIFICATION)
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/all-events', [HomeController::class, 'allEvents'])->name('events.all');
Route::get('/api/events', [HomeController::class, 'getEvents'])->name('api.events');
Route::get('/search', [HomeController::class, 'search'])->name('search');

// Détail d'un événement (SANS AUTHENTIFICATION)
Route::get('/events/{event}', [HomeController::class, 'show'])->name('events.show');

// Événements par catégorie (SANS AUTHENTIFICATION)
Route::get('/categories/{category}', [HomeController::class, 'category'])->name('categories.show');

// Routes d'authentification (générées par Laravel UI)
Auth::routes();

// ==================== PANIER (SANS AUTHENTIFICATION) ====================

Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'show'])->name('show');
    Route::post('/add', [CartController::class, 'add'])->name('add');
    Route::patch('/update', [CartController::class, 'update'])->name('update');
    Route::delete('/remove', [CartController::class, 'remove'])->name('remove');
    Route::delete('/clear', [CartController::class, 'clear'])->name('clear');
    Route::get('/data', [CartController::class, 'getCartData'])->name('data');
});

// ==================== VÉRIFICATION TICKETS (PUBLIQUES) ====================

// Route publique de vérification via QR code (NETTOYER LES DOUBLONS)
Route::get('/verify-ticket/{ticketCode}', [TicketVerificationController::class, 'verify'])
    ->name('tickets.verify');
    
// API de vérification pour scanner
Route::get('/api/verify-ticket/{ticketCode}', [TicketVerificationController::class, 'verifyApi'])
    ->name('api.tickets.verify');
    
// API pour scanner un ticket (marquer comme utilisé)
Route::post('/api/scan-ticket', [TicketVerificationController::class, 'scan'])
    ->name('api.tickets.scan');
    
    //Gestion des pages (CMS)
 Route::get('/page/{slug}', [App\Http\Controllers\PageController::class, 'showCMS'])->name('pages.cms');
// ==================== ROUTES AUTHENTIFIÉES ====================

// Dashboard principal (redirecteur)
Route::middleware(['auth'])->get('/dashboard', function() {
    $user = auth()->user();
    
    // Debug pour voir le rôle
    \Log::info('User role: ' . $user->role);
    
    if ($user->isAdmin()) {
        return redirect()->route('admin.dashboard');
    } elseif ($user->isPromoteur()) {
        return redirect()->route('promoteur.dashboard');
    } elseif ($user->isAcheteur()) {
        return redirect()->route('acheteur.dashboard');
    }
    
    // Fallback avec plus d'infos
    \Log::error('Rôle utilisateur non reconnu: ' . $user->role . ' pour l\'utilisateur ID: ' . $user->id);
    return redirect()->route('home')->with('error', 'Rôle utilisateur non reconnu: ' . $user->role);
})->name('dashboard');

Route::middleware(['auth'])->group(function () {
    
    // ==================== CHECKOUT ====================
    
    Route::prefix('checkout')->name('checkout.')->group(function () {
        Route::post('/direct', [CheckoutController::class, 'direct'])->name('direct');
        Route::get('/', [CheckoutController::class, 'show'])->name('show');
        Route::post('/process', [CheckoutController::class, 'process'])->name('process');
        // nouvelle route
    
        Route::get('/confirmation/{order}', [CheckoutController::class, 'confirmation'])->name('confirmation');
    });
    
    // ==================== TICKETS (GÉNÉRAL) ====================
    
    Route::get('/tickets/{ticket}/download', [TicketController::class, 'download'])->name('tickets.download');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    
    // ==================== ACHETEUR ====================
    
    Route::middleware(['acheteur', 'layout:acheteur'])->prefix('acheteur')->name('acheteur.')->group(function () {
        Route::get('/dashboard', [AcheteurController::class, 'dashboard'])->name('dashboard');
        Route::get('/tickets', [AcheteurController::class, 'myTickets'])->name('tickets');
        Route::get('/orders', [AcheteurController::class, 'orders'])->name('orders');
        Route::get('/order/{order}', [AcheteurController::class, 'orderDetail'])->name('order.detail');
        Route::get('/order/{order}/download', [AcheteurController::class, 'downloadTickets'])->name('order.download');
        Route::get('/orders/{order}/qr-codes', [AcheteurController::class, 'getOrderQRCodes'])->name('orders.qr-codes');
        Route::get('/ticket/{ticket}', [AcheteurController::class, 'showTicket'])->name('ticket.show');
        Route::get('/profile', [AcheteurController::class, 'profile'])->name('profile');
        Route::patch('/profile', [AcheteurController::class, 'updateProfile'])->name('profile.update');
        Route::get('/favorites', [AcheteurController::class, 'favorites'])->name('favorites');
        Route::post('/favorites/{event}', [AcheteurController::class, 'addToFavorites'])->name('favorites.add');
        Route::delete('/favorites/{event}', [AcheteurController::class, 'removeFromFavorites'])->name('favorites.remove');
    });
    
    // ==================== PROMOTEUR ====================
    
    Route::middleware(['promoteur', 'layout:promoteur'])->prefix('promoteur')->name('promoteur.')->group(function () {
        // Dashboard principal
        Route::get('/dashboard', [PromoteurController::class, 'dashboard'])->name('dashboard');
        
        // Gestion des événements
        Route::get('/events', [PromoteurController::class, 'events'])->name('events.index');
        Route::get('/events/create', [PromoteurController::class, 'create'])->name('events.create');
        Route::post('/events', [PromoteurController::class, 'store'])->name('events.store');
        Route::get('/events/{event}', [PromoteurController::class, 'show'])->name('events.show');
        Route::get('/events/{event}/edit', [PromoteurController::class, 'edit'])->name('events.edit');
        Route::patch('/events/{event}', [PromoteurController::class, 'update'])->name('events.update');
        Route::delete('/events/{event}', [PromoteurController::class, 'destroy'])->name('events.destroy');
        
        // Publication d'événements
        Route::post('/events/{event}/publish', [PromoteurController::class, 'publish'])->name('events.publish');
        Route::post('/events/{event}/unpublish', [PromoteurController::class, 'unpublish'])->name('events.unpublish');
        
        // Gestion des types de tickets
        Route::get('/events/{event}/tickets/create', [TicketTypeController::class, 'create'])->name('events.tickets.create');
        Route::post('/events/{event}/tickets', [TicketTypeController::class, 'store'])->name('events.tickets.store');
        Route::get('/events/{event}/tickets', [TicketTypeController::class, 'index'])->name('events.tickets.index');
        Route::get('/events/{event}/tickets/{ticketType}/edit', [TicketTypeController::class, 'edit'])->name('events.tickets.edit');
        Route::patch('/events/{event}/tickets/{ticketType}', [TicketTypeController::class, 'update'])->name('events.tickets.update');
        Route::delete('/events/{event}/tickets/{ticketType}', [TicketTypeController::class, 'destroy'])->name('events.tickets.destroy');
        Route::patch('/events/{event}/tickets/{ticketType}/toggle', [TicketTypeController::class, 'toggle'])->name('events.tickets.toggle');
        
        // Scanner QR (ROUTES NETTOYÉES - SUPPRESSION DES DOUBLONS)
        Route::get('/scanner', [PromoteurController::class, 'scanner'])->name('scanner');
        Route::post('/scanner/verify', [PromoteurController::class, 'verifyTicket'])->name('scanner.verify');
        Route::get('/scanner/stats', [PromoteurController::class, 'getScanStats'])->name('scanner.stats');
        Route::get('/scanner/recent', [PromoteurController::class, 'getRecentScans'])->name('scanner.recent');
        Route::get('/scanner/search', [PromoteurController::class, 'searchTicket'])->name('scanner.search');
        
        // Ventes et commissions
        Route::get('/sales', [PromoteurController::class, 'sales'])->name('sales');
        Route::get('/commissions', [PromoteurController::class, 'commissions'])->name('commissions');
        
        // Rapports
        Route::get('/reports', [PromoteurController::class, 'reports'])->name('reports');
        Route::get('/reports/export', [PromoteurController::class, 'exportData'])->name('reports.export');
        
        // Profil promoteur
        Route::get('/profile', [PromoteurController::class, 'profile'])->name('profile');
    });
    
    // ==================== ADMIN ====================
    
    Route::middleware(['admin', 'layout:admin'])->prefix('admin')->name('admin.')->group(function () {
        // Dashboard principal
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        // Gestion des pages (CMS) - À ajouter dans la section admin

        Route::resource('pages', \App\Http\Controllers\Admin\PageController::class);
        Route::post('/pages/{page}/duplicate', [\App\Http\Controllers\Admin\PageController::class, 'duplicate'])->name('pages.duplicate');
        Route::patch('/pages/{page}/toggle-status', [\App\Http\Controllers\Admin\PageController::class, 'toggleStatus'])->name('pages.toggleStatus');
        Route::post('/pages/reorder', [\App\Http\Controllers\Admin\PageController::class, 'reorder'])->name('pages.reorder');
       
        
        // Gestion utilisateurs
        Route::get('/users', [AdminController::class, 'storeUser'])->name('users.store');
        Route::get('/users/create', [AdminController::class, 'createUser'])->name('users.create');
        Route::get('/users/{user}/edit', [AdminController::class, 'editUser'])->name('users.edit');
        Route::patch('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
        Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('users.destroy');
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::get('/users/{user}', [AdminController::class, 'showUser'])->name('users.show');

        // Gestion événements
        // Dans la section admin, assurez-vous d'avoir :
        Route::get('/events', [AdminController::class, 'events'])->name('events');
        Route::get('/events/{event}', [AdminController::class, 'showEvent'])->name('events.show');
        Route::get('/events/{event}/edit', [AdminController::class, 'editEvent'])->name('events.edit');
        Route::patch('/events/{event}', [AdminController::class, 'updateEvent'])->name('events.update');
        Route::delete('/events/{event}', [AdminController::class, 'destroyEvent'])->name('events.destroy');
        Route::patch('/events/{event}/status', [AdminController::class, 'updateEventStatus'])->name('events.updateStatus');
        Route::post('/events/bulk-update', [AdminController::class, 'bulkUpdateEvents'])->name('events.bulkUpdate');
        Route::post('/events/bulk-delete', [AdminController::class, 'bulkDeleteEvents'])->name('events.bulkDelete');
        
        // Gestion tickets
        
        Route::get('/tickets', [AdminController::class, 'tickets'])->name('tickets');
        Route::get('/tickets/{ticket}', [AdminController::class, 'showTicket'])->name('tickets.show');
        Route::patch('/tickets/{ticket}/mark-used', [AdminController::class, 'markTicketUsed'])->name('tickets.markUsed');
        Route::patch('/tickets/{ticket}/cancel', [AdminController::class, 'cancelTicket'])->name('tickets.cancel');
        Route::patch('/tickets/{ticket}/reactivate', [AdminController::class, 'reactivateTicket'])->name('tickets.reactivate');
        Route::get('/tickets/{ticket}/download', [AdminController::class, 'downloadTicketPDF'])->name('tickets.download');
        
        // Gestion commandes
        Route::get('/orders', [AdminController::class, 'orders'])->name('orders');
        Route::get('/orders/{order}', [AdminController::class, 'orderDetail'])->name('orders.show');
        Route::patch('/orders/{order}/status', [AdminController::class, 'updateOrderStatus'])->name('orders.updateStatus');
        Route::post('/orders/bulk-update', [AdminController::class, 'bulkUpdateOrders'])->name('orders.bulkUpdate');
        Route::delete('/orders/{order}', [AdminController::class, 'destroyOrder'])->name('orders.destroy');
        Route::patch('/orders/{order}/refund', [AdminController::class, 'refundOrder'])->name('orders.refund');
        Route::post('/orders/bulk-delete', [AdminController::class, 'bulkDeleteOrders'])->name('orders.bulkDelete');
        Route::post('/orders/bulk-export', [AdminController::class, 'bulkExportOrders'])->name('orders.bulkExport');
        Route::post('/orders/{order}/resend-email', [AdminController::class, 'resendOrderEmail'])->name('orders.resendEmail');
        Route::get('/orders/{order}/pdf', [AdminController::class, 'downloadOrderPDF'])->name('orders.pdf');
        Route::get('/orders/export', [AdminController::class, 'exportOrders'])->name('orders.export');
        
        // Commissions et paiements
        Route::get('/commissions', [AdminController::class, 'commissions'])->name('commissions');
        Route::post('/commissions/{commission}/pay', [AdminController::class, 'payCommission'])->name('commissions.pay');
        Route::post('/commissions/{commission}/hold', [AdminController::class, 'holdCommission'])->name('commissions.hold');
        Route::post('/commissions/{commission}/release', [AdminController::class, 'releaseCommission'])->name('commissions.release');
        
        // Exports et rapports
        Route::get('/commissions/export', [AdminController::class, 'exportCommissions'])->name('export.commissions');
        Route::get('/revenues/export/{period}', [AdminController::class, 'exportRevenues'])->name('export.revenues');
        Route::get('/promoters/export', [AdminController::class, 'exportPromoters'])->name('export.promoters');
        Route::get('/accounting/export/{period}', [AdminController::class, 'exportAccounting'])->name('export.accounting');
        Route::get('/export/all', [AdminController::class, 'exportAll'])->name('export.all');
        Route::get('/export/financial', [AdminController::class, 'exportFinancial'])->name('export.financial');
        Route::get('/export/users', [AdminController::class, 'exportUsers'])->name('export.users');
        Route::get('/export/events', [AdminController::class, 'exportEvents'])->name('export.events');
        Route::get('/export/orders', [AdminController::class, 'exportOrders'])->name('export.orders');
        Route::get('/export/tickets', [AdminController::class, 'exportTickets'])->name('export.tickets');
        // Paramètres système
        Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
        Route::patch('/settings', [AdminController::class, 'updateSettings'])->name('settings.update');
        Route::post('/settings/test-email', [AdminController::class, 'testEmail'])->name('settings.test-email');
        Route::post('/settings/backup', [AdminController::class, 'backupSystem'])->name('settings.backup');
        Route::post('/settings/clear-cache', [AdminController::class, 'clearCache'])->name('settings.clear-cache');

        Route::prefix('emails')->name('emails.')->group(function () {
        Route::get('/', [AdminController::class, 'emailDashboard'])->name('dashboard');
        Route::post('/test', [AdminController::class, 'testEmail'])->name('test');
        Route::get('/templates', [AdminController::class, 'emailTemplates'])->name('templates');
        Route::post('/orders/{order}/resend', [AdminController::class, 'resendOrderEmail'])->name('resend');
        });
        
        // Routes temporaires et liens dashboard
        Route::get('/reports', function () { 
            return view('admin.reports'); 
        })->name('reports');
        
        Route::get('/commissions/pending', function () { 
            return redirect()->route('admin.commissions', ['status' => 'pending']); 
        })->name('commissions.pending');
        
        Route::get('/events/no-sales', function () { 
            return view('admin.events-no-sales'); 
        })->name('events.no-sales');
        
        Route::get('/promoters/inactive', function () { 
            return view('admin.promoters-inactive'); 
        })->name('promoters.inactive');
        
        Route::get('/promoters/{user}', function ($user) { 
            return view('admin.promoter-detail', ['promoter' => \App\Models\User::findOrFail($user)]); 
        })->name('promoters.show');
        
        // Profil admin
        Route::get('/profile', [AdminController::class, 'profile'])->name('profile');
    });
    
    // Route profil générique (fallback)
    Route::get('/profile', function () {
        $user = auth()->user();
        
        if ($user->isAcheteur()) {
            return redirect()->route('acheteur.profile');
        } elseif ($user->isPromoteur()) {
            return redirect()->route('promoteur.profile');
        } elseif ($user->isAdmin()) {
            return redirect()->route('admin.profile');
        }
        
        return view('profile', compact('user'));
    })->name('profile');
});

// ==================== ROUTES API (OPTIONNEL) ====================

Route::middleware(['auth'])->prefix('api')->name('api.')->group(function () {
    // Routes API futures si nécessaires
});

// ==================== ROUTES DE DEBUG (À RETIRER EN PRODUCTION) ====================

# Route de test (ajoutez temporairement dans routes/web.php)
Route::get('/test-qr-methods', function() {
    if (!auth()->check() || !auth()->user()->isAdmin()) abort(403);
    
    $service = app(\App\Services\QRCodeService::class);
    return $service->testAllMethods();
});


# Ajoutez cette route temporaire pour tester
Route::get('/test-qr-diagnostic', [App\Http\Controllers\HomeController::class, 'testQR']);
Route::get('/test-ticket-simple', function() {
    $ticket = App\Models\Ticket::where('ticket_code', 'TKT-NYUEWEI5')->first();
    
    return [
        'ticket_code' => $ticket->ticket_code,
        'status' => $ticket->status,
        'has_order_item' => $ticket->order_item_id ? 'Oui' : 'Non',
        'order_item' => $ticket->orderItem,
        'main_order' => $ticket->getMainOrder(),
        'event_title' => $ticket->ticketType->event->title ?? 'Pas d\'événement'
    ];
});


// À ajouter temporairement dans routes/web.php pour débugger

Route::get('/debug-events', function() {
    if (!auth()->check() || !auth()->user()->isAdmin()) {
        abort(403);
    }
    
    try {
        // Test de base
        $event = \App\Models\Event::first();
        
        if (!$event) {
            return 'Aucun événement trouvé dans la base de données';
        }
        
        $debug = [
            'event_id' => $event->id,
            'event_title' => $event->title,
            'event_structure' => $event->toArray(),
            
            // Test des relations
            'has_category' => $event->category ? 'OUI' : 'NON',
            'has_promoteur' => $event->promoteur ? 'OUI' : 'NON',
            'has_ticket_types' => $event->ticketTypes->count(),
            'has_tickets' => $event->tickets->count(),
            'has_orders' => $event->orders->count(),
            
            // Test des méthodes
            'total_revenue' => $event->totalRevenue(),
            'tickets_sold_count' => method_exists($event, 'getTicketsSoldCount') ? $event->getTicketsSoldCount() : 'MÉTHODE MANQUANTE',
            'orders_count' => method_exists($event, 'getOrdersCount') ? $event->getOrdersCount() : 'MÉTHODE MANQUANTE',
            'commission_earned' => method_exists($event, 'getCommissionEarned') ? $event->getCommissionEarned() : 'MÉTHODE MANQUANTE',
            'progress_percentage' => method_exists($event, 'getProgressPercentage') ? $event->getProgressPercentage() : 'MÉTHODE MANQUANTE',
            
            // Structure tables
            'events_columns' => \Schema::getColumnListing('events'),
            'ticket_types_columns' => \Schema::getColumnListing('ticket_types'),
            'tickets_columns' => \Schema::getColumnListing('tickets'),
            'orders_columns' => \Schema::getColumnListing('orders'),
        ];
        
        return response()->json($debug, 200, [], JSON_PRETTY_PRINT);
        
    } catch (\Exception $e) {
        return [
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'trace' => $e->getTraceAsString()
        ];
    }
});
// ==================== PAGES LÉGALES ET INFORMATIONS ====================
Route::get('/pages/{page}', [\App\Http\Controllers\Admin\PageController::class, 'show'])->name('pages.show');

Route::get('/about', [App\Http\Controllers\PageController::class, 'about'])->name('pages.about');
Route::get('/how-it-works', [App\Http\Controllers\PageController::class, 'howItWorks'])->name('pages.how-it-works');
Route::get('/faq', [App\Http\Controllers\PageController::class, 'faq'])->name('pages.faq');
Route::get('/contact', [App\Http\Controllers\PageController::class, 'contact'])->name('pages.contact');
Route::post('/contact', [App\Http\Controllers\PageController::class, 'submitContact'])->name('pages.contact.submit');

// Pages légales
Route::get('/terms-of-service', [App\Http\Controllers\PageController::class, 'termsOfService'])->name('pages.terms');
Route::get('/privacy-policy', [App\Http\Controllers\PageController::class, 'privacyPolicy'])->name('pages.privacy');
Route::get('/legal-mentions', [App\Http\Controllers\PageController::class, 'legalMentions'])->name('pages.legal');

// Guide promoteur
Route::get('/promoter-guide', [App\Http\Controllers\PageController::class, 'promoterGuide'])->name('pages.promoter-guide');
Route::get('/pricing', [App\Http\Controllers\PageController::class, 'pricing'])->name('pages.pricing');

// Support
Route::get('/support', [App\Http\Controllers\PageController::class, 'support'])->name('pages.support');
Route::post('/support', [App\Http\Controllers\PageController::class, 'submitSupport'])->name('pages.support.submit');

// ==================== ÉVÉNEMENTS PAR CATÉGORIE ====================

// Ces routes utilisent déjà votre HomeController
Route::get('/events/concerts', function() {
    return redirect()->route('home', ['category' => 1]); // ID de la catégorie Concert
})->name('events.concerts');

Route::get('/events/theatre', function() {
    return redirect()->route('home', ['category' => 2]); // ID de la catégorie Théâtre
})->name('events.theatre');

Route::get('/events/sports', function() {
    return redirect()->route('home', ['category' => 3]); // ID de la catégorie Sports
})->name('events.sports');

Route::get('/events/conferences', function() {
    return redirect()->route('home', ['category' => 4]); // ID de la catégorie Conférences
})->name('events.conferences');

Route::get('/events/festivals', function() {
    return redirect()->route('home', ['category' => 5]); // ID de la catégorie Festivals
})->name('events.festivals');