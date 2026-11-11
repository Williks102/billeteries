<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
| Espace admin — chargé depuis web.php avec :
|   middleware admin, préfixe /admin, noms admin.*
|
| Règle : les routes « fixes » (bulk-action, reorder, export…) et les
| redirections de compatibilité sont déclarées AVANT leur Route::resource,
| sinon `/{param}` les capture (ex. /events/no-sales → events.show).
*/

// ===== Tableau de bord, finances, profil =====
Route::get('dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
Route::get('commissions', [AdminController::class, 'commissions'])->name('commissions');
Route::get('revenues', [AdminController::class, 'revenues'])->name('revenues');
Route::get('profile', [AdminController::class, 'profile'])->name('profile');
Route::patch('profile', [AdminController::class, 'updateProfile'])->name('profile.update');

Route::prefix('finances')->name('finances.')->group(function () {
    Route::patch('commissions/{commission}/status', [AdminController::class, 'updateCommissionStatus'])->name('commissions.update-status');
    Route::post('commissions/bulk-pay', [AdminController::class, 'bulkPayCommissions'])->name('commissions.bulk-pay');
});

// ===== Analytics =====
Route::get('analytics', [AdminController::class, 'analytics'])->name('analytics');
Route::prefix('analytics')->name('analytics.')->group(function () {
    Route::get('data', [AdminController::class, 'analyticsData'])->name('data');
    Route::post('filter', [AdminController::class, 'analyticsFilter'])->name('filter');
    Route::get('export', [AdminController::class, 'analyticsExport'])->name('export');

    Route::controller(AnalyticsController::class)->group(function () {
        Route::get('realtime', 'realtime')->name('realtime');
        Route::get('events/{event}', 'event')->name('events.show');
        Route::get('promoters/{promoter}', 'promoter')->name('promoters.show');
        Route::post('compare', 'compare')->name('compare');
        Route::get('cart-abandonment', 'cartAbandonment')->name('cart-abandonment');
        Route::get('sales-heatmap', 'salesHeatmap')->name('sales-heatmap');
        Route::get('cohort', 'cohort')->name('cohort');
        Route::get('predictions', 'predictions')->name('predictions');
    });
});

// ===== Rapports =====
Route::controller(AdminController::class)->prefix('reports')->name('reports.')->group(function () {
    Route::get('/', 'reports')->name('index');
    Route::get('sales', 'salesReport')->name('sales');
    Route::get('financial', 'financialReport')->name('financial');
    Route::get('users', 'usersReport')->name('users');
    Route::get('events', 'eventsReport')->name('events');
    Route::get('export/{type}', 'export')->name('export');
});

// ===== Emails =====
Route::controller(AdminController::class)->prefix('emails')->name('emails.')->group(function () {
    Route::get('/', 'emailDashboard')->name('dashboard');
    Route::post('test', 'testEmail')->name('test');
    Route::get('templates', 'emailTemplates')->name('templates');
    Route::post('orders/{order}/resend', 'resendOrderEmail')->name('resend');
});

// ===== Redirections de compatibilité (déclarées avant les ressources) =====
Route::redirect('commissions/pending', '/admin/commissions?status=pending')->name('commissions.pending');
Route::redirect('events/no-sales', '/admin/events?filter=no-sales')->name('events.no-sales');
Route::redirect('promoters/inactive', '/admin/users?role=promoteur&status=inactive')->name('promoters.inactive');
Route::get('promoters/{user}', fn ($user) => redirect()->route('admin.users.show', $user))->name('promoters.show');

// ===== Utilisateurs =====
Route::post('users/bulk-action', [UserController::class, 'bulkAction'])->name('users.bulk-action');
Route::get('users-export', [UserController::class, 'export'])->name('users.export');
Route::patch('users/{user}/toggle-email', [UserController::class, 'toggleEmailVerification'])->name('users.toggle-email');
Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
Route::resource('users', UserController::class);

// ===== Événements =====
Route::post('events/bulk-action', [EventController::class, 'bulkAction'])->name('events.bulk-action');
Route::get('events-export', [EventController::class, 'export'])->name('events.export');
Route::get('intervention-dashboard', [EventController::class, 'interventionDashboard'])->name('intervention-dashboard');
Route::get('api/intervention-events', [EventController::class, 'getInterventionEvents'])->name('api.intervention-events');
Route::controller(EventController::class)->prefix('events/{event}')->name('events.')->group(function () {
    Route::patch('status', 'updateStatus')->name('update-status');
    Route::get('manage-tickets', 'manageTickets')->name('manage-tickets');
    Route::post('store-tickets', 'storeTickets')->name('store-tickets');
    Route::post('quick-publish', 'quickPublish')->name('quick-publish');
    Route::post('quick-unpublish', 'quickUnpublish')->name('quick-unpublish');
    Route::get('audit-history', 'auditHistory')->name('audit-history');
    Route::post('restore-promoter-control', 'restorePromoterControl')->name('restore-promoter-control');
});
Route::resource('events', EventController::class);

// ===== Commandes =====
Route::post('orders/bulk-action', [OrderController::class, 'bulkAction'])->name('orders.bulk-action');
Route::get('orders-export', [OrderController::class, 'export'])->name('orders.export');
Route::controller(OrderController::class)->prefix('orders/{order}')->name('orders.')->group(function () {
    Route::patch('status', 'updateStatus')->name('update-status');
    Route::post('refund', 'refund')->name('refund');
    Route::post('resend-email', 'resendEmail')->name('resend-email');
});
Route::resource('orders', OrderController::class)->only(['index', 'show', 'edit', 'update']);

// ===== Billets =====
Route::post('tickets/bulk-action', [TicketController::class, 'bulkAction'])->name('tickets.bulk-action');
Route::get('tickets-export', [TicketController::class, 'export'])->name('tickets.export');
Route::controller(TicketController::class)->prefix('tickets/{ticket}')->name('tickets.')->group(function () {
    Route::patch('mark-used', 'markUsed')->name('mark-used');
    Route::patch('cancel', 'cancel')->name('cancel');
    Route::patch('reactivate', 'reactivate')->name('reactivate');
    Route::get('download', 'download')->name('download');
});
Route::resource('tickets', TicketController::class)->only(['index', 'show', 'edit', 'update']);

// ===== Catégories =====
Route::post('categories/reorder', [CategoryController::class, 'reorder'])->name('categories.reorder');
Route::get('categories-export', [CategoryController::class, 'export'])->name('categories.export');
Route::controller(CategoryController::class)->prefix('categories/{category}')->name('categories.')->group(function () {
    Route::patch('toggle-status', 'toggleStatus')->name('toggle-status');
    Route::post('duplicate', 'duplicate')->name('duplicate');
    Route::post('merge', 'merge')->name('merge');
});
Route::resource('categories', CategoryController::class);

// ===== Pages CMS =====
Route::post('pages/reorder', [PageController::class, 'reorder'])->name('pages.reorder');
Route::controller(PageController::class)->prefix('pages/{page}')->name('pages.')->group(function () {
    Route::post('duplicate', 'duplicate')->name('duplicate');
    Route::patch('toggle-status', 'toggleStatus')->name('toggle-status');
});
Route::resource('pages', PageController::class);

// ===== Paramètres =====
Route::controller(SettingsController::class)->prefix('settings')->name('settings.')->group(function () {
    Route::post('test-email', 'testEmail')->name('test-email');
    Route::post('backup', 'backup')->name('backup');
    Route::post('clear-cache', 'clearCache')->name('clear-cache');
    Route::post('optimize-db', 'optimizeDatabase')->name('optimize-db');
    Route::post('cleanup-files', 'cleanupFiles')->name('cleanup-files');
});
Route::resource('settings', SettingsController::class)->only(['index', 'store']);
