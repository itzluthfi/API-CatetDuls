<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\TransactionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/


// ===================================
// PUBLIC ROUTES
// ===================================
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('api.password.email');
});


Route::get('/pub-categories', [CategoryController::class, 'publicIndex']);

// ===================================
// PROTECTED ROUTES (AUTH REQUIRED)
// ===================================
Route::middleware('auth:sanctum')->group(function () {

    // ======================
    // AUTH
    // ======================
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
    });

    // ======================
    // USER
    // ======================
    Route::prefix('user')->group(function () {
        Route::get('/profile', [UserController::class, 'profile']);
        Route::put('/profile', [UserController::class, 'updateProfile']);
        

        Route::post('/photo', [UserController::class, 'uploadPhoto']);
        Route::delete('/photo', [UserController::class, 'deletePhoto']);

        Route::get('/statistics', [UserController::class, 'statistics']);

        Route::get('/preferences', [UserController::class, 'getPreferences']);
        Route::put('/preferences', [UserController::class, 'updatePreferences']);

        Route::delete('/account', [UserController::class, 'deleteAccount']);
    });

    Route::prefix('photos')->group(function () {
        Route::get('/profiles/{filename}', [UserController::class, 'servePhoto']);
        // Nanti bisa ditambahkan routes untuk foto lainnya
        // Route::get('/transactions/{filename}', [TransactionController::class, 'servePhoto']);
    });

    // Admin routes
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/{user}', [UserController::class, 'show']);
    });

    // ======================
    // BOOKS (MANUAL CRUD)
    // ======================
    Route::prefix('books')->group(function () {
        Route::get('/', [BookController::class, 'index']);
        Route::post('/', [BookController::class, 'store']);
        Route::get('/{id}', [BookController::class, 'show']);
        Route::put('/{id}', [BookController::class, 'update']);
        Route::delete('/{id}', [BookController::class, 'destroy']);

        // Optional nested detail
        Route::get('/{id}/wallets', [BookController::class, 'wallets']);
        Route::get('/{id}/categories', [BookController::class, 'categories']);
    });

    // ======================
    // WALLETS (MANUAL CRUD)
    // ======================
    Route::prefix('wallets')->group(function () {
        Route::get('/', [WalletController::class, 'index']);
        Route::post('/', [WalletController::class, 'store']);
        Route::get('/{id}', [WalletController::class, 'show']);
        Route::put('/{id}', [WalletController::class, 'update']);
        Route::delete('/{id}', [WalletController::class, 'destroy']);
    });

    // ======================
    // CATEGORIES (MANUAL CRUD)
    // ======================
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::post('/', [CategoryController::class, 'store']);
        Route::get('/{id}', [CategoryController::class, 'show']);
        Route::put('/{id}', [CategoryController::class, 'update']);
        Route::delete('/{id}', [CategoryController::class, 'destroy']);
    });

    // ======================
    // TRANSACTIONS (MANUAL CRUD + CUSTOM)
    // ======================
    Route::prefix('transactions')->group(function () {

        // CRUD
        Route::get('/', [TransactionController::class, 'index']);
        Route::post('/', [TransactionController::class, 'store']);
        Route::get('/{id}', [TransactionController::class, 'show']);
        Route::put('/{id}', [TransactionController::class, 'update']);
        Route::delete('/{id}', [TransactionController::class, 'destroy']);

        // Custom endpoints
        Route::get('/summary', [TransactionController::class, 'summary']);
        Route::get('/by-category', [TransactionController::class, 'byCategory']);
        Route::get('/by-date', [TransactionController::class, 'byDate']);
        Route::post('/bulk-delete', [TransactionController::class, 'bulkDelete']);
    });
    // ======================
    // BOOK CLOSINGS
    // ======================
    Route::prefix('book-closings')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\BookClosingController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Api\BookClosingController::class, 'store']);
        Route::get('/{id}', [App\Http\Controllers\Api\BookClosingController::class, 'show']);
        Route::put('/{id}', [App\Http\Controllers\Api\BookClosingController::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\Api\BookClosingController::class, 'destroy']);
    });

    // ======================
    // MEMOS
    // ======================
    Route::prefix('memos')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\MemoController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Api\MemoController::class, 'store']);
        Route::get('/{id}', [App\Http\Controllers\Api\MemoController::class, 'show']);
        Route::put('/{id}', [App\Http\Controllers\Api\MemoController::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\Api\MemoController::class, 'destroy']);
    });

    // ======================
    // TAGS
    // ======================
    Route::prefix('tags')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\TagController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Api\TagController::class, 'store']);
        Route::get('/{id}', [App\Http\Controllers\Api\TagController::class, 'show']);
        Route::put('/{id}', [App\Http\Controllers\Api\TagController::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\Api\TagController::class, 'destroy']);
    });
});

Route::options('/{any}', function () {
    return response()->json();
})->where('any', '.*');
