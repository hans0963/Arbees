<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InventoryLogController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderItemController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SalesReportController;
use Illuminate\Support\Facades\Route;

// ──────────────────────────────────────────────────────────
// Public Routes (guests only)
// ──────────────────────────────────────────────────────────

Route::middleware('guest')->group(function () {
    Route::get('/login',     [App\Http\Controllers\Auth\LoginController::class,    'showLoginForm'])->name('login');
    Route::post('/login',    [App\Http\Controllers\Auth\LoginController::class,    'login']);
    Route::get('/register',  [App\Http\Controllers\Auth\RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [App\Http\Controllers\Auth\RegisterController::class, 'register']);
});

Route::post('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');

// ──────────────────────────────────────────────────────────
// Online Ordering (customers — authenticated)
// ──────────────────────────────────────────────────────────

Route::middleware(['auth'])->prefix('shop')->name('shop.')->group(function () {
    Route::get('/',                  [ProductController::class, 'index'])->name('index');           // shop/index.blade.php
    Route::get('/product/{product}', [ProductController::class, 'show'])->name('product');          // shop/product.blade.php
    Route::get('/cart',              [OrderController::class,   'create'])->name('cart');            // shop/cart.blade.php
    Route::post('/cart/checkout',    [OrderController::class,   'store'])->name('checkout');
    Route::get('/orders',            [OrderController::class,   'index'])->name('orders');           // shop/orders.blade.php
    Route::get('/orders/{order}',    [OrderController::class,   'show'])->name('orders.show');       // shop/order-show.blade.php
});

// ──────────────────────────────────────────────────────────
// Admin / Staff Routes
// ──────────────────────────────────────────────────────────

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {

    // Dashboard ─────────────────────────────────────────
    Route::get('/dashboard', fn() => view('admin.dashboard'))->name('dashboard');

    // Categories ────────────────────────────────────────
    Route::resource('categories', CategoryController::class);

    // Products ──────────────────────────────────────────
    Route::resource('products', ProductController::class);

    // Inventory ─────────────────────────────────────────
    // low-stock must be before {product} to avoid being caught as a model binding
    Route::get('inventory/low-stock',         [InventoryController::class, 'lowStock'])->name('inventory.low-stock');
    Route::get('inventory',                   [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('inventory/{product}',         [InventoryController::class, 'show'])->name('inventory.show');
    Route::post('inventory/{product}/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');

    // Inventory Logs ────────────────────────────────────
    // product/{product} must be before {inventoryLog} to avoid being caught as a model binding
    Route::get('inventory-logs',                     [InventoryLogController::class, 'index'])->name('inventory-logs.index');
    Route::get('inventory-logs/product/{product}',   [InventoryLogController::class, 'forProduct'])->name('inventory-logs.product');
    Route::get('inventory-logs/{inventoryLog}',      [InventoryLogController::class, 'show'])->name('inventory-logs.show');

    // Orders ────────────────────────────────────────────
    Route::resource('orders', OrderController::class)->except(['edit', 'update']);
    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
    Route::patch('orders/{order}/pay',    [OrderController::class, 'markAsPaid'])->name('orders.pay');
    Route::patch('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

    // Order Items ───────────────────────────────────────
    Route::get('orders/{order}/items',                [OrderItemController::class, 'index'])->name('order-items.index');
    Route::post('orders/{order}/items',               [OrderItemController::class, 'store'])->name('order-items.store');
    Route::patch('orders/{order}/items/{orderItem}',  [OrderItemController::class, 'update'])->name('order-items.update');
    Route::delete('orders/{order}/items/{orderItem}', [OrderItemController::class, 'destroy'])->name('order-items.destroy');

    // Sales Reports ─────────────────────────────────────
    Route::get('reports',             [SalesReportController::class, 'index'])->name('reports.index');
    Route::post('reports/generate',   [SalesReportController::class, 'generate'])->name('reports.generate');
    Route::get('reports/{report}',    [SalesReportController::class, 'show'])->name('reports.show');
    Route::delete('reports/{report}', [SalesReportController::class, 'destroy'])->name('reports.destroy');
});

// ──────────────────────────────────────────────────────────
// Root Redirect
// ──────────────────────────────────────────────────────────

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('login');
});