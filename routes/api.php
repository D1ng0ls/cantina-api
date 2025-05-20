<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\ApiUserController;
use App\Http\Controllers\Api\ApiMenuController;
use App\Http\Controllers\Api\ApiCategoriaController;
use App\Http\Controllers\Api\ApiPedidoController;

Route::prefix('cantina')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::post('/register', [AuthController::class, 'store'])->name('register');
        Route::post('/login', [AuthController::class, 'login'])->name('login');
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);

        // Route::get('/user', [ApiUserController::class, 'user']);
        // Route::put('/user/{user}', [ApiUserController::class, 'update']);
        // Route::get('/user/{user}', [ApiUserController::class, 'destroy']);
        
        Route::get('/menu', [ApiMenuController::class, 'show']);
        Route::get('/menu/{produto}', [ApiMenuController::class,'showById']);
        Route::get('/menu/categoria/{categoria}', [ApiMenuController::class,'showByCategoria']);
        Route::post('/menu', [ApiMenuController::class,'store']);
        Route::patch('/menu/{produto}', [ApiMenuController::class,'update']);
        Route::delete('/menu/{produto}', [ApiMenuController::class,'destroy']);

        Route::get('/categorias', [ApiCategoriaController::class,'show']);
        Route::post('/categorias', [ApiCategoriaController::class,'store']);
        Route::patch('/categorias/{categoria}', [ApiCategoriaController::class,'update']);
        Route::delete('/categorias/{categoria}', [ApiCategoriaController::class,'destroy']);

        Route::get('/pedido', [ApiPedidoController::class,'show']);
        Route::post('/pedido', [ApiPedidoController::class,'store']);
        Route::patch('/pedido/{pedido}', [ApiPedidoController::class,'update']);
        Route::delete('/pedido/{pedido}', [ApiPedidoController::class,'destroy']);
    });
});