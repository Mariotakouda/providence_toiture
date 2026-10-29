<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\OnlineRequestController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductVariantController;
use App\Http\Controllers\PublicRequestController;
use App\Http\Controllers\QuoteConversionController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/gammes/{category}', [GalleryController::class, 'show'])->name('gammes.show');

Route::post('/demande', [PublicRequestController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('public-requests.store');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])
        ->middleware('throttle:6,1')->name('password.email');

    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])
        ->middleware('throttle:6,1')->name('password.store');
});

/*
|--------------------------------------------------------------------------
| Utilisateurs connectés et actifs
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Administration : utilisateurs, rôles, paramètres, audit
    Route::middleware('permission:users.manage')->group(function () {
        Route::resource('users', UserController::class)->except(['show', 'destroy']);
        Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])
            ->name('users.toggle-status');

        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    });

    Route::middleware('permission:settings.manage')->group(function () {
        Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
    });

    Route::get('audit-logs', [AuditLogController::class, 'index'])
        ->middleware('permission:audit.view')->name('audit-logs.index');

    // Catalogue
    Route::resource('categories', CategoryController::class)->except(['show', 'destroy']);
    Route::patch('categories/{category}/toggle-status', [CategoryController::class, 'toggleStatus'])
        ->name('categories.toggle-status');

    Route::resource('products', ProductController::class)->except(['destroy']);
    Route::patch('products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])
        ->name('products.toggle-status');

    Route::resource('products.variants', ProductVariantController::class)
        ->only(['create', 'store', 'edit', 'update'])
        ->scoped();
    Route::patch('products/{product}/variants/{variant}/toggle-status', [ProductVariantController::class, 'toggleStatus'])
        ->scopeBindings()
        ->name('products.variants.toggle-status');

    // Clients et fournisseurs
    Route::resource('customers', CustomerController::class)->except(['destroy']);
    Route::patch('customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])
        ->name('customers.toggle-status');

    Route::resource('suppliers', SupplierController::class)->except(['destroy']);
    Route::patch('suppliers/{supplier}/toggle-status', [SupplierController::class, 'toggleStatus'])
        ->name('suppliers.toggle-status');

    // Stock
    Route::middleware('permission:stock.view')->group(function () {
        Route::get('stock-movements', [StockMovementController::class, 'index'])->name('stock-movements.index');
    });

    Route::middleware('permission:stock.move|stock.adjust')->group(function () {
        Route::get('stock-movements/create', [StockMovementController::class, 'create'])->name('stock-movements.create');
        Route::post('stock-movements', [StockMovementController::class, 'store'])->name('stock-movements.store');
    });

    Route::middleware('permission:stock.adjust')->group(function () {
        Route::get('stock/inventory', [StockAdjustmentController::class, 'create'])->name('stock.inventory.create');
        Route::post('stock/inventory', [StockAdjustmentController::class, 'store'])->name('stock.inventory.store');
    });

    // Devis
    Route::resource('quotes', QuoteController::class)->except(['destroy']);
    Route::post('quotes/{quote}/transition', [QuoteController::class, 'transition'])->name('quotes.transition');
    Route::get('quotes/{quote}/pdf', [QuoteController::class, 'pdf'])->name('quotes.pdf');
    Route::post('quotes/{quote}/convert', [QuoteConversionController::class, 'store'])->name('quotes.convert');

    // Commandes
    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('orders/{order}/transition', [OrderController::class, 'transition'])->name('orders.transition');

    // Livraisons
    Route::get('deliveries', [DeliveryController::class, 'index'])->name('deliveries.index');
    Route::get('orders/{order}/deliveries/create', [DeliveryController::class, 'create'])->name('deliveries.create');
    Route::post('orders/{order}/deliveries', [DeliveryController::class, 'store'])->name('deliveries.store');
    Route::get('deliveries/{delivery}', [DeliveryController::class, 'show'])->name('deliveries.show');
    Route::post('deliveries/{delivery}/transition', [DeliveryController::class, 'transition'])->name('deliveries.transition');
    Route::get('deliveries/{delivery}/pdf', [DeliveryController::class, 'pdf'])->name('deliveries.pdf');

    // Factures et paiements
    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('orders/{order}/invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
    Route::post('orders/{order}/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
    Route::put('invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
    Route::post('invoices/{invoice}/transition', [InvoiceController::class, 'transition'])->name('invoices.transition');
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');

    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::post('payments/{payment}/cancel', [PaymentController::class, 'cancel'])->name('payments.cancel');
    Route::get('payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');

    // Rapports
    Route::middleware('permission:reports.sales')->group(function () {
        Route::get('reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
        Route::get('reports/sales/export', [ReportController::class, 'exportSales'])->name('reports.sales.export');
    });

    Route::middleware('permission:reports.financial')->group(function () {
        Route::get('reports/financial', [ReportController::class, 'financial'])->name('reports.financial');
        Route::get('reports/financial/export', [ReportController::class, 'exportPayments'])->name('reports.financial.export');
    });

    // Demandes en ligne des clients
    Route::middleware('permission:requests.view')->group(function () {
        Route::get('online-requests', [OnlineRequestController::class, 'index'])->name('online-requests.index');
        Route::get('online-requests/{onlineRequest}', [OnlineRequestController::class, 'show'])->name('online-requests.show');
    });

    Route::middleware('permission:requests.manage')->group(function () {
        Route::post('online-requests/{onlineRequest}/take', [OnlineRequestController::class, 'take'])->name('online-requests.take');
        Route::post('online-requests/{onlineRequest}/convert', [OnlineRequestController::class, 'convert'])->name('online-requests.convert');
        Route::post('online-requests/{onlineRequest}/close', [OnlineRequestController::class, 'close'])->name('online-requests.close');
    });
});
