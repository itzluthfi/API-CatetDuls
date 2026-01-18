<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\AdminDashboardController;

Route::get('/', [AdminDashboardController::class, 'home'])->name('home');

// Admin Login Page (accessible by anyone, redirects if already logged in)
Route::get('/admin/login', [AdminDashboardController::class, 'showLogin'])->name('admin.login');

// Admin Guest Routes (POST login only)
Route::middleware('guest')->group(function () {
    Route::post('/admin/login', [AdminDashboardController::class, 'login']);
});

// Admin Protected Routes
Route::middleware(['auth'])->group(function () {
    Route::post('/admin/logout', [AdminDashboardController::class, 'logout'])->name('admin.logout');
    
    Route::prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'dashboard'])->name('admin.dashboard');
        Route::get('/users', [AdminDashboardController::class, 'users'])->name('admin.users');
        Route::get('/api/users', [AdminDashboardController::class, 'getUsersApi'])->name('admin.api.users');
        Route::get('/api-docs', [AdminDashboardController::class, 'apiDocs'])->name('admin.api-docs');
    });
});

Route::get('password/reset', [AuthController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('password/email', [AuthController::class, 'forgotPassword'])->name('password.email');
Route::get('password/reset/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
Route::post('password/reset', [AuthController::class, 'resetPassword'])->name('password.update');
