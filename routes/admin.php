<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\BackupController;
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

// ===== Journal d'activité (lecture seule) =====
Route::get('activity', [ActivityLogController::class, 'index'])->name('activity.index');

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
Route::controller(EventController::class)->prefix('events/{event}')->name('events.')->group(function () {
    Route::patch('status', 'updateStatus')->name('update-status');
    Route::post('quick-publish', 'quickPublish')->name('quick-publish');
    Route::post('quick-unpublish', 'quickUnpublish')->name('quick-unpublish');
    Route::post('duplicate', 'duplicate')->name('duplicate');
});
Route::resource('events', EventController::class);

// ===== Commandes =====
Route::post('orders/bulk-action', [OrderController::class, 'bulkAction'])->name('orders.bulk-action');
Route::get('orders-export', [OrderController::class, 'export'])->name('orders.export');
Route::controller(OrderController::class)->prefix('orders/{order}')->name('orders.')->group(function () {
    Route::patch('status', 'updateStatus')->name('update-status');
    Route::post('confirm-payment', 'confirmPayment')->name('confirm-payment');
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
Route::resource('categories', CategoryController::class);

// ===== Pages CMS =====
Route::post('pages/reorder', [PageController::class, 'reorder'])->name('pages.reorder');
Route::controller(PageController::class)->prefix('pages/{page}')->name('pages.')->group(function () {
    Route::post('duplicate', 'duplicate')->name('duplicate');
    Route::patch('toggle-status', 'toggleStatus')->name('toggle-status');
});
Route::resource('pages', PageController::class);

// ===== Sauvegardes (page d'information, lecture seule) =====
Route::get('backups', [BackupController::class, 'index'])->name('backups.index');

// ===== Paramètres =====
Route::resource('settings', SettingsController::class)->only(['index', 'store']);
