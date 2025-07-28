<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Acheteur\AcheteurController;
use App\Http\Controllers\Admin\AdminController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Page d'accueil avec liste des événements (SANS AUTHENTIFICATION)
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/all-events', [HomeController::class, 'allEvents'])->name('events.all');

// Ajoutez ces routes après vos routes existantes
Route::get('/api/events', [HomeController::class, 'getEvents'])->name('api.events');
Route::get('/search', [HomeController::class, 'search'])->name('search');

// Détail d'un événement (SANS AUTHENTIFICATION)
Route::get('/events/{event}', [HomeController::class, 'show'])->name('events.show');

// Événements par catégorie (SANS AUTHENTIFICATION)
Route::get('/categories/{category}', [HomeController::class, 'category'])->name('categories.show');

// Routes d'authentification (générées par Laravel UI)
Auth::routes();
Route::get('/test-qr-diagnostic', function() {
    if (!auth()->check() || !auth()->user()->isAdmin()) {
        abort(403);
    }
    
    $results = [];
    
    // 1. Test des extensions PHP
    $results['extensions'] = [
        'gd' => extension_loaded('gd'),
        'imagick' => extension_loaded('imagick'),
        'curl' => extension_loaded('curl'),
        'openssl' => extension_loaded('openssl')
    ];
    
    // 2. Test SimpleSoftwareIO/QrCode
    $results['packages'] = [
        'simplesoftwareio/simple-qrcode' => class_exists('\SimpleSoftwareIO\QrCode\Facades\QrCode')
    ];
    
    // 3. Test du service QRCodeService
    try {
        $qrService = app(\App\Services\QRCodeService::class);
        $results['service_test'] = $qrService->testAllMethods();
    } catch (\Exception $e) {
        $results['service_test'] = [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
    
    // 4. Test avec un vrai ticket
    try {
        $ticket = \App\Models\Ticket::first();
        if ($ticket) {
            $qrBase64 = $qrService->getOrGenerateTicketQR($ticket, 'base64');
            $results['real_ticket_test'] = [
                'ticket_code' => $ticket->ticket_code,
                'qr_generated' => $qrBase64 !== null,
                'qr_length' => $qrBase64 ? strlen($qrBase64) : 0,
                'qr_preview' => $qrBase64 ? substr($qrBase64, 0, 100) . '...' : null
            ];
        }
    } catch (\Exception $e) {
        $results['real_ticket_test'] = [
            'error' => $e->getMessage()
        ];
    }
    
    // 5. Test des permissions de stockage
    try {
        $testFile = 'public/qrcodes/test.txt';
        \Storage::put($testFile, 'test');
        $results['storage_test'] = [
            'writable' => true,
            'path' => storage_path('app/public/qrcodes')
        ];
        \Storage::delete($testFile);
    } catch (\Exception $e) {
        $results['storage_test'] = [
            'writable' => false,
            'error' => $e->getMessage()
        ];
    }
    
    // 6. Test de connectivité externe
    $apis = [
        'google_charts' => 'https://chart.googleapis.com/chart?chs=100x100&cht=qr&chl=test',
        'qr_server' => 'https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=test'
    ];
    
    foreach ($apis as $name => $url) {
        try {
            $response = \Http::timeout(5)->get($url);
            $results['api_connectivity'][$name] = [
                'accessible' => $response->successful(),
                'status' => $response->status(),
                'size' => strlen($response->body())
            ];
        } catch (\Exception $e) {
            $results['api_connectivity'][$name] = [
                'accessible' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Retourner la vue de diagnostic
    return view('diagnostic.qr', compact('results'));
});

// Vue correspondante : resources/views/diagnostic/qr.blade.php

// Routes du panier (ACCESSIBLES SANS AUTHENTIFICATION)
Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'show'])->name('show');
    Route::post('/add', [CartController::class, 'add'])->name('add');
    Route::patch('/update', [CartController::class, 'update'])->name('update');
    Route::delete('/remove', [CartController::class, 'remove'])->name('remove');
    Route::delete('/clear', [CartController::class, 'clear'])->name('clear');
    Route::get('/data', [CartController::class, 'getCartData'])->name('data');
});

// Routes publiques pour vérification des billets
Route::get('/tickets/{ticket}', [App\Http\Controllers\TicketController::class, 'show'])
    ->name('tickets.show');
Route::get('/verify-ticket/{ticketCode}', [App\Http\Controllers\TicketController::class, 'verify'])
    ->name('tickets.verify');
Route::get('/tickets/{ticket}/download', [App\Http\Controllers\TicketController::class, 'download'])
    ->middleware('auth')
    ->name('tickets.download');


// Route de vérification des billets (PUBLIQUE - pour scanner QR codes)
Route::get('/verify-ticket/{ticketCode}', function ($ticketCode) {
    $ticket = \App\Models\Ticket::where('ticket_code', $ticketCode)
        ->with(['ticketType.event.category', 'ticketType.event.promoteur'])
        ->first();
    
    if (!$ticket) {
        abort(404, 'Billet non trouvé');
    }
    
    return view('tickets.verify', compact('ticket'));
})->name('tickets.verify');

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

// Routes protégées par authentification
Route::middleware(['auth'])->group(function () {
    
    // Routes de checkout (nécessitent l'authentification)
    Route::prefix('checkout')->name('checkout.')->group(function () {
        Route::get('/', [CheckoutController::class, 'show'])->name('show');
        Route::post('/process', [CheckoutController::class, 'process'])->name('process');
        Route::get('/confirmation/{order}', [CheckoutController::class, 'confirmation'])->name('confirmation');
    });
    
    // Routes acheteur
    Route::middleware(['acheteur', 'layout:acheteur'])->prefix('acheteur')->name('acheteur.')->group(function () {
        Route::get('/dashboard', [AcheteurController::class, 'dashboard'])->name('dashboard');
        Route::get('/tickets', [AcheteurController::class, 'myTickets'])->name('tickets');
        Route::get('/order/{order}', [AcheteurController::class, 'orderDetail'])->name('order.detail');
        Route::get('/order/{order}/download', [AcheteurController::class, 'downloadTickets'])->name('order.download');
        Route::get('/ticket/{ticket}', [AcheteurController::class, 'showTicket'])->name('ticket.show');
        Route::get('/profile', [AcheteurController::class, 'profile'])->name('profile');
        Route::patch('/profile', [AcheteurController::class, 'updateProfile'])->name('profile.update');
        // Dans la section acheteur
        Route::get('/orders/{order}/qr-codes', [AcheteurController::class, 'getOrderQRCodes'])->name('orders.qr-codes');
        Route::get('/orders', [AcheteurController::class, 'orders'])->name('orders');
    Route::get('/favorites', [AcheteurController::class, 'favorites'])->name('favorites');
    Route::post('/favorites/{event}', [AcheteurController::class, 'addToFavorites'])->name('favorites.add');
    Route::delete('/favorites/{event}', [AcheteurController::class, 'removeFromFavorites'])->name('favorites.remove');
    });
    

Route::middleware(['admin', 'layout:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard principal
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::get('/events', [AdminController::class, 'events'])->name('events');
    Route::get('/orders', [AdminController::class, 'orders'])->name('orders');
    Route::get('/orders/{order}', [AdminController::class, 'orderDetail'])->name('orders.show');
    Route::get('/commissions', [AdminController::class, 'commissions'])->name('commissions');
    Route::post('/commissions/{commission}/pay', [AdminController::class, 'payCommission'])->name('commissions.pay');
    Route::get('/profile', [AdminController::class, 'profile'])->name('profile');
    Route::patch('/orders/{order}/status', [AdminController::class, 'updateOrderStatus'])->name('orders.updateStatus');
    Route::post('/orders/bulk-update', [AdminController::class, 'bulkUpdateOrders'])->name('orders.bulkUpdate');
    Route::get('/orders/{order}/pdf', [AdminController::class, 'downloadOrderPDF'])->name('orders.pdf');
    Route::get('/orders/export', [AdminController::class, 'exportOrders'])->name('orders.export');
    Route::get('/tickets/{ticket}', [AdminController::class, 'showTicket'])->name('tickets.show');
    Route::patch('/tickets/{ticket}/mark-used', [AdminController::class, 'markTicketUsed'])->name('tickets.markUsed');
    Route::get('/tickets/{ticket}/download', [AdminController::class, 'downloadTicketPDF'])->name('tickets.download');

    Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
    Route::get('/users/create', [AdminController::class, 'createUser'])->name('users.create');
    Route::get('/users/{user}/edit', [AdminController::class, 'editUser'])->name('users.edit');
    Route::patch('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('users.destroy');

    Route::get('/export/financial', [AdminController::class, 'exportFinancial'])->name('export.financial');
    Route::get('/export/users', [AdminController::class, 'exportUsers'])->name('export.users');
    Route::get('/export/events', [AdminController::class, 'exportEvents'])->name('export.events');
    Route::get('/export/orders', [AdminController::class, 'exportOrders'])->name('export.orders');
    Route::get('/export/commissions', [AdminController::class, 'exportCommissions'])->name('export.commissions');
    

    Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
    Route::get('/profile', [AdminController::class, 'profile'])->name('profile');
    Route::patch('/orders/{order}/status', [AdminController::class, 'updateOrderStatus'])->name('orders.updateStatus');
    Route::post('/orders/bulk-update', [AdminController::class, 'bulkUpdateOrders'])->name('orders.bulkUpdate');
    Route::get('/orders/{order}/pdf', [AdminController::class, 'downloadOrderPDF'])->name('orders.pdf');
    Route::get('/orders/export', [AdminController::class, 'exportOrders'])->name('orders.export');
    
    Route::get('/settings', function () { 
        return view('admin.settings'); 
    })->name('settings');
    
    // CORRECTION : Enlever le préfixe /admin puisqu'il est déjà dans le groupe
    Route::get('/events/{id}', [AdminController::class, 'eventDetail'])->name('events.detail');
    Route::get('/events', [AdminController::class, 'events'])->name('events');
    Route::get('/events/{event}', [AdminController::class, 'showEvent'])->name('events.show');
    Route::get('/events/{event}/edit', [AdminController::class, 'editEvent'])->name('events.edit');
    Route::patch('/events/{event}', [AdminController::class, 'updateEvent'])->name('events.update');
    Route::delete('/events/{event}', [AdminController::class, 'destroyEvent'])->name('events.destroy');
    
    // Routes pour actions sur events
    Route::patch('/events/{event}/status', [AdminController::class, 'updateEventStatus'])->name('events.updateStatus');
    Route::post('/events/bulk-update', [AdminController::class, 'bulkUpdateEvents'])->name('events.bulkUpdate');
    Route::post('/events/bulk-delete', [AdminController::class, 'bulkDeleteEvents'])->name('events.bulkDelete');
    
    
    
    // Gestion des commissions (exports)
    Route::get('/commissions/export', [AdminController::class, 'exportCommissions'])->name('export.commissions');
    Route::get('/revenues/export/{period}', [AdminController::class, 'exportRevenues'])->name('export.revenues');
    Route::get('/orders/export', [AdminController::class, 'exportOrders'])->name('export.orders');
    Route::get('/promoters/export', [AdminController::class, 'exportPromoters'])->name('export.promoters');
    Route::get('/accounting/export/{period}', [AdminController::class, 'exportAccounting'])->name('export.accounting');
    Route::get('/export/all', [AdminController::class, 'exportAll'])->name('export.all');

    
    // Routes temporaires
    Route::get('/reports', function () { 
        return view('admin.reports'); 
    })->name('reports');
    
    Route::get('/settings', function () { 
        return view('admin.settings'); 
    })->name('settings');
    
    // Routes pour les liens du dashboard
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
});
    
 // Routes promoteur avec middleware promoteur
    Route::middleware(['auth', 'promoteur'])->prefix('promoteur')->name('promoteur.')->group(function () {
    
        // Dashboard principal
        Route::get('/dashboard', [App\Http\Controllers\Promoteur\PromoteurController::class, 'dashboard'])
            ->name('dashboard');
        
        // Gestion des événements
        Route::get('/events', [App\Http\Controllers\Promoteur\PromoteurController::class, 'events'])
            ->name('events.index');
        Route::get('/events/create', [App\Http\Controllers\Promoteur\PromoteurController::class, 'create'])
            ->name('events.create');
        Route::post('/events', [App\Http\Controllers\Promoteur\PromoteurController::class, 'store'])
            ->name('events.store');
        Route::get('/events/{event}', [App\Http\Controllers\Promoteur\PromoteurController::class, 'show'])
            ->name('events.show');
        Route::get('/events/{event}/edit', [App\Http\Controllers\Promoteur\PromoteurController::class, 'edit'])
            ->name('events.edit');
        Route::patch('/events/{event}', [App\Http\Controllers\Promoteur\PromoteurController::class, 'update'])
            ->name('events.update');

        Route::delete('/events/{event}', [App\Http\Controllers\Promoteur\PromoteurController::class, 'destroy'])
            ->name('events.destroy');

        // Routes des types de billets
        Route::get('events/{event}/tickets/create', [App\Http\Controllers\Promoteur\TicketTypeController::class, 'create'])->name('events.tickets.create');
        Route::post('events/{event}/tickets', [App\Http\Controllers\Promoteur\TicketTypeController::class, 'store'])->name('events.tickets.store');
        Route::get('events/{event}/tickets', [App\Http\Controllers\Promoteur\TicketTypeController::class, 'index'])->name('events.tickets.index');
        Route::get('events/{event}/tickets/{ticketType}/edit', [App\Http\Controllers\Promoteur\TicketTypeController::class, 'edit'])->name('events.tickets.edit');
        Route::put('events/{event}/tickets/{ticketType}', [App\Http\Controllers\Promoteur\TicketTypeController::class, 'update'])->name('events.tickets.update');
        Route::delete('events/{event}/tickets/{ticketType}', [App\Http\Controllers\Promoteur\TicketTypeController::class, 'destroy'])->name('events.tickets.destroy');
        Route::patch('events/{event}/tickets/{ticketType}/toggle', [App\Http\Controllers\Promoteur\TicketTypeController::class, 'toggle'])->name('events.tickets.toggle');
        
        // Routes pour publication
        Route::post('events/{event}/publish', [App\Http\Controllers\Promoteur\PromoteurController::class, 'publish'])->name('events.publish');
        Route::post('events/{event}/unpublish', [App\Http\Controllers\Promoteur\PromoteurController::class, 'unpublish'])->name('events.unpublish');   
        
        // Scanner QR
        Route::get('/scanner', [App\Http\Controllers\Promoteur\PromoteurController::class, 'scanner'])
            ->name('scanner');
        Route::post('/scanner/verify', [App\Http\Controllers\Promoteur\PromoteurController::class, 'verifyTicket'])
            ->name('scanner.verify');

            
        // Dans le groupe promoteur
        Route::post('/scanner/verify', [PromoteurController::class, 'verifyTicket'])
            ->name('scanner.verify');
        Route::get('/scanner/stats', [PromoteurController::class, 'getScanStats'])
            ->name('scanner.stats');
        Route::get('/scanner/recent', [PromoteurController::class, 'getRecentScans'])
            ->name('scanner.recent');
        Route::get('/scanner/search', [PromoteurController::class, 'searchTicket'])
             ->name('scanner.search');
        // Ventes
        Route::get('/sales', [App\Http\Controllers\Promoteur\PromoteurController::class, 'sales'])
            ->name('sales');
        
        // Commissions
        Route::get('/commissions', [App\Http\Controllers\Promoteur\PromoteurController::class, 'commissions'])
            ->name('commissions');
        
        // Rapports
        Route::get('/reports', [App\Http\Controllers\Promoteur\PromoteurController::class, 'reports'])
            ->name('reports');
        Route::get('/reports/export', [App\Http\Controllers\Promoteur\PromoteurController::class, 'exportData'])
            ->name('reports.export');
        
        // Profil
        Route::get('/profile', function () {
            return view('promoteur.profile', ['user' => auth()->user()]);
        })->name('profile');

        Route::get('/scanner', [App\Http\Controllers\Promoteur\PromoteurController::class, 'scanner'])->name('scanner');
    Route::get('/sales', [App\Http\Controllers\Promoteur\PromoteurController::class, 'sales'])->name('sales');
    Route::get('/commissions', [App\Http\Controllers\Promoteur\PromoteurController::class, 'commissions'])->name('commissions');
    Route::get('/reports', [App\Http\Controllers\Promoteur\PromoteurController::class, 'reports'])->name('reports');
    Route::get('/profile', [App\Http\Controllers\Promoteur\PromoteurController::class, 'profile'])->name('profile');
    });

    
    // Route profil commune (temporaire, sera remplacée par les profils spécifiques)
    Route::get('/profile', function () {
        $user = auth()->user();
        
        // Rediriger vers le profil spécifique selon le rôle
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

// Routes API pour AJAX (optionnel - pour les futures fonctionnalités)
Route::middleware(['auth'])->prefix('api')->name('api.')->group(function () {
    
    // API pour vérification ticket (pour les promoteurs avec scanner)
    Route::post('/verify-ticket', function (\Illuminate\Http\Request $request) {
        $request->validate([
            'ticket_code' => 'required|string'
        ]);
        
        $ticket = \App\Models\Ticket::where('ticket_code', $request->ticket_code)
            ->with(['ticketType.event'])
            ->first();
        
        if (!$ticket) {
            return response()->json(['success' => false, 'message' => 'Billet non trouvé'], 404);
        }
        
        // Vérifier que l'utilisateur peut scanner ce billet
        $user = auth()->user();
        if (!$user->isAdmin() && 
            (!$user->isPromoteur() || $ticket->ticketType->event->promoteur_id !== $user->id)) {
            return response()->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }
        
        // Marquer le billet comme utilisé si c'est valide
        if ($ticket->status === 'sold') {
            $ticket->markAsUsed();
            return response()->json([
                'success' => true, 
                'message' => 'Billet validé avec succès',
                'ticket' => $ticket->getFullTicketInfo()
            ]);
        } else {
            return response()->json([
                'success' => false, 
                'message' => "Billet non valide (statut: {$ticket->status})",
                'ticket' => $ticket->getFullTicketInfo()
            ]);
        }
    })->name('verify.ticket');
});