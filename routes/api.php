<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\SellerController;
use Illuminate\Support\Facades\Route;

// ── Public ──
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// ── Authenticated ──
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Admin
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/users', [AdminController::class, 'users']);
        Route::post('/users', [AdminController::class, 'storeUser']);
        Route::post('/users/{user}/toggle', [AdminController::class, 'toggleUser']);
        Route::delete('/users/{user}', [AdminController::class, 'deleteUser']);
        Route::get('/drivers-list', [AdminController::class, 'driversList']);
        Route::get('/orders', [AdminController::class, 'orders']);
        Route::get('/orders/{order}', [AdminController::class, 'showOrder']);
        Route::post('/orders/{order}/assign', [AdminController::class, 'assignDriver']);
        Route::post('/orders/{order}/status', [AdminController::class, 'updateStatus']);
        Route::get('/reports', [AdminController::class, 'reports']);
    });

    // Seller
    Route::middleware('role:seller')->prefix('seller')->group(function () {
        Route::get('/dashboard', [SellerController::class, 'dashboard']);
        Route::get('/orders', [SellerController::class, 'orders']);
        Route::post('/orders', [SellerController::class, 'store']);
        Route::get('/orders/{order}', [SellerController::class, 'show']);
        Route::put('/orders/{order}', [SellerController::class, 'update']);
    });

    // Driver
    Route::middleware('role:driver')->prefix('driver')->group(function () {
        Route::get('/dashboard', [DriverController::class, 'dashboard']);
        Route::get('/available', [DriverController::class, 'available']);
        Route::post('/deliveries/{order}/take', [DriverController::class, 'take']);
        Route::get('/deliveries', [DriverController::class, 'active']);
        Route::get('/deliveries/history', [DriverController::class, 'history']);
        Route::get('/deliveries/{order}', [DriverController::class, 'show']);
        Route::post('/deliveries/{order}/status', [DriverController::class, 'updateStatus']);
    });
});
