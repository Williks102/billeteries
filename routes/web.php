<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketVerificationController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\GuestCheckoutController;

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
//Route::post('/api/scan-ticket', [TicketVerificationController::class, 'scan'])->name('api.tickets.scan');

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

Route::prefix('checkout')->name('checkout.')->group(function () {
    
    // Checkout classique (nécessite auth)
    Route::middleware(['auth'])->group(function () {
        Route::get('/', [CheckoutController::class, 'show'])->name('show');
        Route::post('/process', [CheckoutController::class, 'process'])->name('process');
    });
    
    // Checkout invité (sans auth)
    Route::prefix('guest')->name('guest.')->group(function () {
        Route::get('/', [GuestCheckoutController::class, 'show'])->name('show');
        Route::post('/process', [GuestCheckoutController::class, 'process'])->name('process');
        Route::get('/confirmation/{token}', [GuestCheckoutController::class, 'confirmation'])->name('confirmation');
        Route::post('/create-account/{token}', [GuestCheckoutController::class, 'createAccountAfterPurchase'])->name('create-account');
    });
    
    // Redirection intelligente depuis le panier
    Route::get('/choose', function() {
        if (auth()->check()) {
            return redirect()->route('checkout.show');
        }
        return redirect()->route('checkout.guest.show');
    })->name('choose');
});



Route::post('/api/check-email', function(Request $request) {
    try {
        $request->validate(['email' => 'required|email']);
        
        $exists = \App\Models\User::where('email', $request->email)
                                  ->where('is_guest', false) // Exclure les invités
                                  ->exists();
        
        return response()->json([
            'success' => true,
            'available' => !$exists,
            'exists' => $exists
        ]);
        
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Email invalide',
            'errors' => $e->errors()
        ], 422);
        
    } catch (\Exception $e) {
        \Log::error('Erreur API check-email: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => 'Erreur de vérification'
        ], 500);
    }
})->middleware('throttle:60,1'); // Limite à 60 requêtes par minute

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

        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [Promoteur\ReportsController::class, 'index'])->name('index');
            Route::get('/events', [Promoteur\ReportsController::class, 'index'])->defaults('type', 'events')->name('events');
            Route::get('/financial', [Promoteur\ReportsController::class, 'index'])->defaults('type', 'financial')->name('financial');
            Route::get('/export', [Promoteur\ReportsController::class, 'export'])->name('export');
});
        
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

        Route::post('/scanner/verify/{ticketCode}', [TicketVerificationController::class, 'authenticatedVerify'])->name('tickets.authenticated.verify');
        Route::post('/api/authenticated-scan', [TicketVerificationController::class, 'authenticatedScan'])->name('api.authenticated-scan');
        
        
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
    Route::post('/admin/users/{user}/reset-password', [App\Http\Controllers\Admin\UserController::class, 'resetPassword'])
         ->name('admin.users.reset-password');

    
    // ===== DASHBOARD ET FONCTIONS GÉNÉRALES (AdminController) =====
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/commissions', [AdminController::class, 'commissions'])->name('commissions');
    Route::get('/revenues', [AdminController::class, 'revenues'])->name('revenues');
    Route::get('/analytics', [AdminController::class, 'analytics'])->name('analytics');
    // Routes AJAX pour Analytics
    Route::get('/analytics/data', [AdminController::class, 'analyticsData'])->name('analytics.data');
    Route::post('/analytics/filter', [AdminController::class, 'analyticsFilter'])->name('analytics.filter');
    Route::get('/analytics/export', [AdminController::class, 'analyticsExport'])->name('analytics.export');
    
    // Routes supplémentaires pour des analytics spécialisées
    Route::prefix('analytics')->name('analytics.')->group(function () {
        
        // Analytics en temps réel
        Route::get('/realtime', function() {
            $realTimeStats = [
                'active_users' => \App\Models\User::where('last_activity', '>=', now()->subMinutes(5))->count(),
                'orders_today' => \App\Models\Order::whereDate('created_at', today())->count(),
                'revenue_today' => \App\Models\Order::where('payment_status', 'paid')
                    ->whereDate('created_at', today())->sum('total_amount'),
                'tickets_sold_today' => \App\Models\Ticket::where('status', 'sold')
                    ->whereDate('created_at', today())->count(),
                'conversion_rate_today' => 3.2, // À calculer selon vos métriques
                'top_selling_event' => \App\Models\Event::withCount(['tickets as sold_today' => function($query) {
                        $query->where('status', 'sold')->whereDate('created_at', today());
                    }])
                    ->orderBy('sold_today', 'desc')
                    ->first(),
            ];
            
            return response()->json($realTimeStats);
        })->name('realtime');
        
        // Analytics par événement
        Route::get('/events/{event}', function(\App\Models\Event $event) {
            $eventAnalytics = [
                'total_revenue' => $event->orders()->where('payment_status', 'paid')->sum('total_amount'),
                'tickets_sold' => $event->tickets()->where('status', 'sold')->count(),
                'tickets_available' => $event->ticketTypes()->sum('quantity_available'),
                'attendance_rate' => $event->tickets()->where('status', 'used')->count(),
                'daily_sales' => $event->orders()
                    ->selectRaw('DATE(created_at) as date, COUNT(*) as orders, SUM(total_amount) as revenue')
                    ->where('payment_status', 'paid')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get(),
                'ticket_types_performance' => $event->ticketTypes()
                    ->withCount(['tickets as sold' => function($query) {
                        $query->where('status', 'sold');
                    }])
                    ->withSum(['tickets as revenue' => function($query) {
                        $query->where('status', 'sold');
                    }], 'price')
                    ->get(),
                'buyer_demographics' => $event->orders()
                    ->with('user')
                    ->where('payment_status', 'paid')
                    ->get()
                    ->groupBy('user.role')
                    ->map(function($group) {
                        return $group->count();
                    }),
            ];
            
            return response()->json($eventAnalytics);
        })->name('events.show');
        
        // Analytics par promoteur
        Route::get('/promoters/{promoter}', function(\App\Models\User $promoter) {
            if (!$promoter->isPromoter()) {
                return response()->json(['error' => 'Utilisateur non promoteur'], 400);
            }
            
            $promoterAnalytics = [
                'total_events' => $promoter->events()->count(),
                'published_events' => $promoter->events()->where('status', 'published')->count(),
                'total_revenue' => $promoter->events()
                    ->join('orders', 'events.id', '=', 'orders.event_id')
                    ->where('orders.payment_status', 'paid')
                    ->sum('orders.total_amount'),
                'total_tickets_sold' => $promoter->events()
                    ->join('tickets', 'events.id', '=', 'tickets.event_id')
                    ->where('tickets.status', 'sold')
                    ->count(),
                'commission_earned' => $promoter->commissions()->sum('commission_amount'),
                'events_performance' => $promoter->events()
                    ->withCount(['tickets as sold' => function($query) {
                        $query->where('status', 'sold');
                    }])
                    ->withSum(['orders as revenue' => function($query) {
                        $query->where('payment_status', 'paid');
                    }], 'total_amount')
                    ->orderBy('revenue', 'desc')
                    ->take(10)
                    ->get(),
                'monthly_performance' => collect(range(0, 11))->map(function($i) use ($promoter) {
                    $date = now()->subMonths($i);
                    return [
                        'month' => $date->format('M Y'),
                        'revenue' => $promoter->events()
                            ->join('orders', 'events.id', '=', 'orders.event_id')
                            ->where('orders.payment_status', 'paid')
                            ->whereYear('orders.created_at', $date->year)
                            ->whereMonth('orders.created_at', $date->month)
                            ->sum('orders.total_amount'),
                        'tickets_sold' => $promoter->events()
                            ->join('tickets', 'events.id', '=', 'tickets.event_id')
                            ->where('tickets.status', 'sold')
                            ->whereYear('tickets.created_at', $date->year)
                            ->whereMonth('tickets.created_at', $date->month)
                            ->count(),
                    ];
                })->reverse()->values(),
            ];
            
            return response()->json($promoterAnalytics);
        })->name('promoters.show');
        
        // Comparaison de périodes
        Route::post('/compare', function(Request $request) {
            $request->validate([
                'period1_start' => 'required|date',
                'period1_end' => 'required|date',
                'period2_start' => 'required|date',
                'period2_end' => 'required|date',
            ]);
            
            $period1 = [$request->period1_start, $request->period1_end];
            $period2 = [$request->period2_start, $request->period2_end];
            
            $comparison = [
                'period1' => [
                    'revenue' => \App\Models\Order::where('payment_status', 'paid')
                        ->whereBetween('created_at', $period1)->sum('total_amount'),
                    'orders' => \App\Models\Order::whereBetween('created_at', $period1)->count(),
                    'users' => \App\Models\User::whereBetween('created_at', $period1)->count(),
                    'events' => \App\Models\Event::whereBetween('created_at', $period1)->count(),
                ],
                'period2' => [
                    'revenue' => \App\Models\Order::where('payment_status', 'paid')
                        ->whereBetween('created_at', $period2)->sum('total_amount'),
                    'orders' => \App\Models\Order::whereBetween('created_at', $period2)->count(),
                    'users' => \App\Models\User::whereBetween('created_at', $period2)->count(),
                    'events' => \App\Models\Event::whereBetween('created_at', $period2)->count(),
                ]
            ];
            
            // Calculer les pourcentages de variation
            $comparison['changes'] = [];
            foreach ($comparison['period1'] as $key => $value1) {
                $value2 = $comparison['period2'][$key];
                $change = $value2 > 0 ? (($value1 - $value2) / $value2) * 100 : 0;
                $comparison['changes'][$key] = round($change, 2);
            }
            
            return response()->json($comparison);
        })->name('compare');
        
        // Analytics des abandons de panier
        Route::get('/cart-abandonment', function() {
            // Simulation des données d'abandon de panier
            // À adapter selon votre système de tracking de panier
            $cartAnalytics = [
                'total_carts_created' => 1250, // Sessions avec ajout au panier
                'completed_orders' => 890, // Commandes finalisées
                'abandonment_rate' => 28.8, // (1250 - 890) / 1250 * 100
                'abandoned_value' => 850000, // Valeur des paniers abandonnés
                'abandonment_stages' => [
                    'cart' => 15, // Abandon au panier
                    'checkout' => 8, // Abandon au checkout
                    'payment' => 5.8, // Abandon au paiement
                ],
                'recovery_opportunities' => [
                    'email_campaigns' => 35, // % récupérable par email
                    'sms_campaigns' => 20, // % récupérable par SMS
                    'retargeting' => 25, // % récupérable par pub
                ],
                'top_abandoned_events' => \App\Models\Event::withCount(['orders as abandoned' => function($query) {
                        $query->where('payment_status', 'pending')
                              ->where('created_at', '<', now()->subHours(24));
                    }])
                    ->orderBy('abandoned', 'desc')
                    ->take(10)
                    ->get(['id', 'title', 'abandoned'])
            ];
            
            return response()->json($cartAnalytics);
        })->name('cart-abandonment');
        
        // Heatmap des ventes par heure
        Route::get('/sales-heatmap', function() {
            $heatmapData = collect(range(0, 23))->map(function($hour) {
                return [
                    'hour' => $hour,
                    'sales' => \App\Models\Order::where('payment_status', 'paid')
                        ->whereRaw('HOUR(created_at) = ?', [$hour])
                        ->whereBetween('created_at', [now()->subDays(30), now()])
                        ->count(),
                    'revenue' => \App\Models\Order::where('payment_status', 'paid')
                        ->whereRaw('HOUR(created_at) = ?', [$hour])
                        ->whereBetween('created_at', [now()->subDays(30), now()])
                        ->sum('total_amount')
                ];
            });
            
            return response()->json($heatmapData);
        })->name('sales-heatmap');
        
        // Cohort analysis (analyse de cohortes)
        Route::get('/cohort', function() {
            $cohortData = collect();
            
            // Analyser les cohortes des 12 derniers mois
            for ($i = 11; $i >= 0; $i--) {
                $cohortMonth = now()->subMonths($i);
                $newUsers = \App\Models\User::whereYear('created_at', $cohortMonth->year)
                    ->whereMonth('created_at', $cohortMonth->month)
                    ->pluck('id');
                
                $cohortAnalysis = [
                    'month' => $cohortMonth->format('M Y'),
                    'new_users' => $newUsers->count(),
                    'retention' => []
                ];
                
                // Calculer la rétention pour chaque mois suivant
                for ($retentionMonth = 0; $retentionMonth <= min(6, now()->diffInMonths($cohortMonth)); $retentionMonth++) {
                    $targetMonth = $cohortMonth->copy()->addMonths($retentionMonth);
                    $activeUsers = \App\Models\Order::whereIn('user_id', $newUsers)
                        ->whereYear('created_at', $targetMonth->year)
                        ->whereMonth('created_at', $targetMonth->month)
                        ->distinct('user_id')
                        ->count();
                    
                    $retentionRate = $newUsers->count() > 0 ? ($activeUsers / $newUsers->count()) * 100 : 0;
                    $cohortAnalysis['retention'][$retentionMonth] = round($retentionRate, 1);
                }
                
                $cohortData->push($cohortAnalysis);
            }
            
            return response()->json($cohortData);
        })->name('cohort');
        
        // Prédictions avec Machine Learning (simulation)
        Route::get('/predictions', function() {
            // Simulation de prédictions ML
            // En production, vous intégreriez un vrai modèle ML
            $predictions = [
                'next_week' => [
                    'revenue' => \App\Models\Order::where('payment_status', 'paid')
                        ->whereBetween('created_at', [now()->subWeek(), now()])
                        ->sum('total_amount') * 1.08, // +8% prévu
                    'orders' => \App\Models\Order::whereBetween('created_at', [now()->subWeek(), now()])
                        ->count() * 1.05, // +5% prévu
                    'confidence' => 85 // % de confiance
                ],
                'next_month' => [
                    'revenue' => \App\Models\Order::where('payment_status', 'paid')
                        ->whereBetween('created_at', [now()->subMonth(), now()])
                        ->sum('total_amount') * 1.15, // +15% prévu
                    'orders' => \App\Models\Order::whereBetween('created_at', [now()->subMonth(), now()])
                        ->count() * 1.12, // +12% prévu
                    'confidence' => 72 // % de confiance
                ],
                'seasonal_trends' => [
                    'peak_months' => ['Décembre', 'Juillet', 'Août'],
                    'low_months' => ['Janvier', 'Février', 'Septembre'],
                    'growth_trend' => 'positive', // positive, negative, stable
                    'growth_rate' => 12.5 // % annuel
                ],
                'recommendations' => [
                    'Augmenter l\'inventaire pour les événements musicaux en décembre',
                    'Prévoir des promotions en janvier et février',
                    'Cibler les jeunes adultes (25-35 ans) pour augmenter les ventes'
                ]
            ];
            
            return response()->json($predictions);
        })->name('predictions');
    });
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

    
    // Gestion des billets par admin
    Route::get('events/{event}/manage-tickets', [AdminEventController::class, 'manageTickets'])->name('events.manage-tickets');
    Route::post('events/{event}/store-tickets', [AdminEventController::class, 'storeTickets'])->name('events.store-tickets');

// Tableau de bord des interventions
    Route::get('intervention-dashboard', [AdminEventController::class, 'interventionDashboard'])->name('intervention-dashboard');

// Actions rapides
    Route::post('events/{event}/quick-publish', [AdminEventController::class, 'quickPublish'])->name('events.quick-publish');
    Route::post('events/{event}/quick-unpublish', [AdminEventController::class, 'quickUnpublish'])->name('events.quick-unpublish');

// Historique et audit
    Route::get('events/{event}/audit-history', [AdminEventController::class, 'auditHistory'])->name('events.audit-history');

// 

// APIs pour AJAX
    Route::get('api/intervention-events', [AdminEventController::class, 'getInterventionEvents'])->name('api.intervention-events');
    Route::post('events/{event}/restore-promoter-control', [AdminEventController::class, 'restorePromoterControl'])->name('events.restore-promoter-control');
    
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