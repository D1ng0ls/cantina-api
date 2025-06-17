<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OpeningHourController;

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
            Route::delete('/', [UserController::class, 'destroy']);
        });

        Route::group(['prefix' => 'menu', 'as' => 'menu.'], function () {
            Route::get('/', [MenuController::class, 'index']);
            Route::get('/{product}', [MenuController::class, 'showById']);
            Route::get('/categoria/{category}', [MenuController::class, 'showByCategoria']);
            Route::post('/', [MenuController::class, 'store']);
            Route::patch('/{product}', [MenuController::class, 'update']);
            Route::delete('/{product}', [MenuController::class, 'destroy']);
        });

        Route::group(['prefix' => 'categories', 'as' => 'categories.'], function () {
            Route::get('/', [CategoryController::class, 'index']);
            Route::get('/{category}', [CategoryController::class, 'showById']);
            Route::post('/', [CategoryController::class, 'store']);
            Route::patch('/{category}', [CategoryController::class, 'update']);
            Route::delete('/{category}', [CategoryController::class, 'destroy']);
        });

        Route::group(['prefix' => 'order', 'as' => 'order.'], function () {
            Route::get('/', [OrderController::class, 'show']);
            Route::post('/', [OrderController::class, 'store']);
            Route::patch('/{order}', [OrderController::class, 'update']);
            Route::delete('/{order}', [OrderController::class, 'destroy']);
        });

        Route::group(['prefix' => 'opening_hours', 'as' => 'opening_hours.'], function () {
            Route::get('/', [OpeningHourController::class, 'index']);
            Route::get('/{day}', [OpeningHourController::class, 'showByWeekday']);
            Route::post('/', [OpeningHourController::class, 'store']);
            Route::patch('/{openingHour}', [OpeningHourController::class, 'update']);
            Route::delete('/{openingHour}', [OpeningHourController::class, 'destroy']);
        });
    });
});
