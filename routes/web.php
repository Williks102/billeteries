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

// Détail d'un événement (SANS AUTHENTIFICATION)
Route::get('/events/{event}', [HomeController::class, 'show'])->name('events.show');

// Événements par catégorie (SANS AUTHENTIFICATION)
Route::get('/categories/{category}', [HomeController::class, 'category'])->name('categories.show');

// Routes d'authentification (générées par Laravel UI)
Auth::routes();

// Routes du panier (ACCESSIBLES SANS AUTHENTIFICATION)
Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'show'])->name('show');
    Route::post('/add', [CartController::class, 'add'])->name('add');
    Route::patch('/update', [CartController::class, 'update'])->name('update');
    Route::delete('/remove', [CartController::class, 'remove'])->name('remove');
    Route::delete('/clear', [CartController::class, 'clear'])->name('clear');
    Route::get('/data', [CartController::class, 'getCartData'])->name('data');
});

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
    Route::middleware(['acheteur'])->prefix('acheteur')->name('acheteur.')->group(function () {
        Route::get('/dashboard', [AcheteurController::class, 'dashboard'])->name('dashboard');
        Route::get('/tickets', [AcheteurController::class, 'myTickets'])->name('tickets');
        Route::get('/order/{order}', [AcheteurController::class, 'orderDetail'])->name('order.detail');
        Route::get('/order/{order}/download', [AcheteurController::class, 'downloadTickets'])->name('order.download');
        Route::get('/ticket/{ticket}', [AcheteurController::class, 'showTicket'])->name('ticket.show');
        Route::get('/profile', [AcheteurController::class, 'profile'])->name('profile');
        Route::patch('/profile', [AcheteurController::class, 'updateProfile'])->name('profile.update');
    });
    
    // Routes admin avec middleware admin
    Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
        // Dashboard principal
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        
        // Gestion des commissions
        Route::get('/commissions', [AdminController::class, 'commissions'])->name('commissions');
        Route::post('/commissions/{commission}/pay', [AdminController::class, 'payCommission'])->name('commissions.pay');
        Route::get('/commissions/export', [AdminController::class, 'exportCommissions'])->name('export.commissions');
        Route::get('/revenues/export/{period}', [AdminController::class, 'exportRevenues'])->name('export.revenues');
        Route::get('/orders/export', [AdminController::class, 'exportOrders'])->name('export.orders');
        Route::get('/promoters/export', [AdminController::class, 'exportPromoters'])->name('export.promoters');
        Route::get('/accounting/export/{period}', [AdminController::class, 'exportAccounting'])->name('export.accounting');
        Route::get('/admin/events/{id}', [AdminController::class, 'eventDetail'])->name('admin.events.detail');
        Route::get('/admin/events/{id}', [AdminController::class, 'eventDetail'])->name('admin.events.detail');

        
        // Routes temporaires (à développer)
        // Vue avec utilisateurs dynamiques
        Route::get('/users', [AdminController::class, 'users'])->name('users');

        // Vue avec événements dynamiques
        Route::get('/events', [AdminController::class, 'events'])->name('events');

        // Vue avec commandes dynamiques
        Route::get('/orders', [AdminController::class, 'orders'])->name('orders');

        // Détail d'une commande
        Route::get('/orders/{order}', [AdminController::class, 'orderDetail'])->name('orders.show');

        Route::get('/reports', function () { return view('admin.reports'); })->name('reports');
        Route::get('/settings', function () { return view('admin.settings'); })->name('settings');
        
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
        
        Route::get('/orders/{order}', function ($order) { 
            return view('admin.order-detail', ['order' => \App\Models\Order::findOrFail($order)]); 
        })->name('orders.show');
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