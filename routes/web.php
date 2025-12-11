<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('password/reset', [AuthController::class, 'showLinkRequestForm'])->name('password.request');


Route::post('password/email', [AuthController::class, 'forgotPassword'])->name('password.email');

Route::get('password/reset/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');


Route::post('password/reset', [AuthController::class, 'resetPassword'])->name('password.update');
