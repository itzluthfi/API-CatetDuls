<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ===== PUBLIC ROUTES (No Authentication) =====
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// ===== PROTECTED ROUTES (Require Authentication) =====
Route::middleware('auth:sanctum')->group(function () {

    // ===== AUTH ROUTES =====
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
    });

    // ===== USER ROUTES =====
    Route::prefix('user')->group(function () {
        // Profile
        Route::get('/profile', [UserController::class, 'profile']);
        Route::put('/profile', [UserController::class, 'updateProfile']);

        // Photo
        Route::post('/photo', [UserController::class, 'uploadPhoto']);
        Route::delete('/photo', [UserController::class, 'deletePhoto']);

        // Statistics
        Route::get('/statistics', [UserController::class, 'statistics']);

        // Preferences
        Route::get('/preferences', [UserController::class, 'getPreferences']);
        Route::put('/preferences', [UserController::class, 'updatePreferences']);

        // Delete account
        Route::delete('/account', [UserController::class, 'deleteAccount']);
    });

    // Admin routes (optional)
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/{user}', [UserController::class, 'show']);
    });

    // ===== BOOKS =====
    Route::apiResource('books', BookController::class);

    // ===== WALLETS =====
    Route::apiResource('wallets', WalletController::class);

    // ===== CATEGORIES =====
    Route::apiResource('categories', CategoryController::class);

    // ===== TRANSACTIONS =====
    // Basic CRUD
    Route::apiResource('transactions', TransactionController::class);

    // Additional transaction endpoints
    Route::prefix('transactions')->group(function () {
        // GET /api/transactions/summary?book_id=1&start_date=xxx&end_date=xxx
        Route::get('/summary', [TransactionController::class, 'summary'])
            ->name('transactions.summary');

        // GET /api/transactions/by-category?book_id=1&type=PENGELUARAN
        Route::get('/by-category', [TransactionController::class, 'byCategory'])
            ->name('transactions.by-category');

        // GET /api/transactions/by-date?book_id=1&start_date=xxx&end_date=xxx
        Route::get('/by-date', [TransactionController::class, 'byDate'])
            ->name('transactions.by-date');

        // POST /api/transactions/bulk-delete
        Route::post('/bulk-delete', [TransactionController::class, 'bulkDelete'])
            ->name('transactions.bulk-delete');
    });
});
