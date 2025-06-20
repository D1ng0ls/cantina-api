<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OpeningHourController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\Webhook\MercadoPago\MessageController;

Route::prefix('cantina')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::post('/register', [AuthController::class, 'store'])->name('register');
        Route::post('/login', [AuthController::class, 'login'])->name('login');
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::group(['prefix' => 'profile', 'as' => 'profile.'], function () {
            Route::get('/', [UserController::class, 'show']);
            Route::patch('/', [UserController::class, 'update']);
            Route::post('/change-password', [UserController::class, 'changePassword']);
        });

        Route::get('/products/inactives', [ProductController::class, 'indexInactive'])->name('products.indexInactive');
        Route::delete('/products/remove-image/{product}', [ProductController::class, 'removeImage'])->name('products.removeImage');
        Route::post('/products/active/{product}', [ProductController::class, 'toggleActive'])->name('products.toggleActive');

        // ROTA GERAL (RESOURCE) DEPOIS
        Route::apiResource('products', ProductController::class);
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('orders', OrderController::class);

        Route::apiResource('opening-hours', OpeningHourController::class)->parameters([
            'opening-hours' => 'openingHour'
        ]);
        Route::get('/opening-hours/by-weekday/{day}', [OpeningHourController::class, 'showByWeekday'])->name('opening-hours.showByWeekday');
    });
});

Route::post('/mp/webhook', [MessageController::class, 'webhook'])->name('mp.webhook');
