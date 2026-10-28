<?php

use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Store Order & Inventory Mini-System
|--------------------------------------------------------------------------
*/

// Products API
Route::prefix('products')->group(function () {
    Route::get('/low-stock', [ProductController::class, 'lowStock'])->name('api.products.low-stock');
    Route::get('/', [ProductController::class, 'index'])->name('api.products.index');
    Route::get('/{product}', [ProductController::class, 'show'])->name('api.products.show');
});

// Orders API
Route::prefix('orders')->group(function () {
    Route::post('/', [OrderController::class, 'store'])->name('api.orders.store');
    Route::get('/', [OrderController::class, 'index'])->name('api.orders.index');
    Route::get('/{order}', [OrderController::class, 'show'])->name('api.orders.show');
});

// Customers API
Route::prefix('customers')->group(function () {
    Route::get('/{email}/orders', [CustomerController::class, 'orders'])->name('api.customers.orders');
    Route::get('/{email}', [CustomerController::class, 'show'])->name('api.customers.show');
});
