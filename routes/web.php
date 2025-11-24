<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProviderProductController;
use App\Http\Controllers\CustomerProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\RatingController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

// 1. RUTA RAÍZ: Redirige directamente al login al iniciar el servidor.
Route::get('/', function () {
    return view('auth.login');
});

// Rutas de autenticación POST
Route::post('/register', [AuthController::class, 'register'])
    ->name('register')
    ->middleware('throttle:6,1')
    ->withoutMiddleware([VerifyCsrfToken::class]);
Route::post('/login', [AuthController::class, 'login'])
    ->name('login')
    ->middleware('throttle:6,1')
    ->withoutMiddleware([VerifyCsrfToken::class]);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Rutas GET de formularios
Route::get('/register', function () {
    return view('auth.register');
})->name('register.form');

Route::get('/login', function () {
    return view('auth.login');
})->name('login.form');


Route::middleware('auth')->group(function () {

    // 1. RUTA DEL CLIENTE (Requiere rol 'customer')
    Route::get('/customer/home', [CustomerProductController::class, 'index'])
        ->name('customer.home')
        ->middleware('role:customer');
    Route::get('/providers/{provider}', [CustomerProductController::class, 'profile'])
        ->name('provider.profile')
        ->middleware('role:customer');

    // 2. RUTA DEL PROVEEDOR (Requiere rol 'provider')
    Route::get('/provider/dashboard', [ProviderProductController::class, 'index'])
        ->name('provider.dashboard')
        ->middleware('role:provider');

    // RUTA DEL ADMINISTRADOR (Requiere rol 'admin')
    Route::get('/admin/dashboard', function () { return view('admin.dashboard'); })
        ->name('admin.dashboard')
        ->middleware('role:admin');

    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/users', [AdminController::class, 'usersIndex'])->name('admin.users');
        Route::post('/users/{user}/role', [AdminController::class, 'usersUpdateRole'])->name('admin.users.role');
        Route::get('/orders', [AdminController::class, 'ordersIndex'])->name('admin.orders');
        Route::post('/orders/{order}/status', [AdminController::class, 'ordersUpdateStatus'])->name('admin.orders.status');

        // CRUD de Proveedores
        Route::get('/providers', [AdminController::class, 'providersIndex'])->name('admin.providers');
        Route::get('/providers/create', [AdminController::class, 'providersCreate'])->name('admin.providers.create');
        Route::post('/providers', [AdminController::class, 'providersStore'])->name('admin.providers.store');
        Route::get('/providers/{provider}/edit', [AdminController::class, 'providersEdit'])->name('admin.providers.edit');
        Route::put('/providers/{provider}', [AdminController::class, 'providersUpdate'])->name('admin.providers.update');
        Route::delete('/providers/{provider}', [AdminController::class, 'providersDestroy'])->name('admin.providers.destroy');
    });

    // GRUPO DE RUTAS DEL PROVEEDOR (Requiere rol 'provider')
    Route::prefix('provider')->middleware('role:provider')->group(function () {

        // Rutas del CRUD de Producto (Creación y almacenamiento)
        Route::get('/product/create', [ProviderProductController::class, 'create'])->name('provider.product.create');
        Route::post('/product', [ProviderProductController::class, 'store'])->name('provider.product.store');

        // Rutas del CRUD de Producto (Restantes)
        Route::get('/product/{product}/edit', [ProviderProductController::class, 'edit'])->name('provider.product.edit');
        Route::put('/product/{product}', [ProviderProductController::class, 'update'])->name('provider.product.update');
        Route::delete('/product/{product}', [ProviderProductController::class, 'destroy'])->name('provider.product.destroy');
        Route::get('/reports', [ProviderProductController::class, 'reports'])->name('provider.reports');
        Route::post('/profile/logo', [ProviderProductController::class, 'uploadLogo'])->name('provider.logo.upload');
        Route::post('/profile/location', [ProviderProductController::class, 'updateLocation'])->name('provider.location.update');
    });

    // Carrito del cliente
    Route::prefix('cart')->middleware('role:customer')->group(function () {
        Route::get('/', [CartController::class, 'index'])->name('cart.index');
        Route::post('/add/{product}', [CartController::class, 'add'])->name('cart.add');
        Route::post('/remove/{product}', [CartController::class, 'remove'])->name('cart.remove');
        Route::post('/update/{product}', [CartController::class, 'update'])->name('cart.update');
        Route::post('/delete/{product}', [CartController::class, 'delete'])->name('cart.delete');
        Route::post('/clear', [CartController::class, 'clear'])->name('cart.clear');
        Route::post('/delete-mix/{key}', [CartController::class, 'deleteMix'])->name('cart.deleteMix');
    });

    // Checkout
    Route::middleware('role:customer')->group(function () {
        Route::get('/checkout', [CartController::class, 'checkout'])->name('cart.checkout');
        Route::post('/checkout', [CartController::class, 'place'])->name('cart.place');
        Route::get('/refunds', [CartController::class, 'refundsIndex'])->name('customer.refunds.index');
        Route::get('/refunds/new', [CartController::class, 'refundForm'])->name('customer.refunds.form');
        Route::post('/refunds', [CartController::class, 'submitRefund'])->name('customer.refunds.submit');
        Route::get('/orders', [CartController::class, 'ordersIndex'])->name('orders.index');
        Route::post('/orders/{order}/pay', [CartController::class, 'pay'])->name('orders.pay');
        Route::post('/orders/{order}/gateway', [CartController::class, 'startGatewayPayment'])->name('orders.gateway');
        Route::post('/orders/{order}/cancel', [CartController::class, 'cancel'])->name('orders.cancel');
        Route::post('/orders/{order}/pause', [CartController::class, 'pause'])->name('orders.pause');
        Route::post('/orders/{order}/reschedule', [CartController::class, 'reschedule'])->name('orders.reschedule');
        Route::get('/orders/{order}/invoice', [CartController::class, 'invoiceForm'])->name('orders.invoice.form');
        Route::post('/orders/{order}/invoice', [CartController::class, 'invoiceSubmit'])->name('orders.invoice.submit');
        Route::get('/orders/{order}/receipt', [CartController::class, 'receipt'])->name('orders.receipt');
        Route::post('/orders/{order}/receipt/resend', [CartController::class, 'receiptResend'])->name('orders.receipt.resend');

        Route::post('/ratings/product/{product}', [RatingController::class, 'rateProduct'])->name('ratings.product');
        Route::post('/ratings/provider/{provider}', [RatingController::class, 'rateProvider'])->name('ratings.provider');
    });
});
