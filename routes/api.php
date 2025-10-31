<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductTransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Auth routes
Route::post('/register', [AuthController::class, 'register'])->name('api.register');
Route::post('/login', [AuthController::class, 'login'])->name('api.login');

//grouped route
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile'])->name('api.profile');

    Route::get('/products', [ProductController::class, 'index'])->name('api.products.index');

    Route::get('/products/{id}/transactions', [ProductTransactionController::class, 'index'])->name('api.products.transactions.index');
    Route::post('/products/{id}/transactions/create', [ProductTransactionController::class, 'store'])->name('api.products.transactions.store');
    Route::post('/products/{id}/transactions/{transactionId}', [ProductTransactionController::class, 'update'])->name('api.products.transactions.update');
    Route::delete('/products/{id}/transactions/{transactionId}', [ProductTransactionController::class, 'destroy'])->name('api.products.transactions.destroy');

    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
});
