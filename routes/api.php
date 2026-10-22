<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::post('/registro', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/produtos', [ProductController::class, 'index']);
    Route::post('/produtos', [ProductController::class, 'store']);
    Route::get('/produtos/{produto}', [ProductController::class, 'show']);
    Route::match(['put', 'patch'], '/produtos/{produto}', [ProductController::class, 'update']);
    Route::delete('/produtos/{produto}', [ProductController::class, 'destroy']);

    Route::get('/pedidos', [OrderController::class, 'index']);
    Route::post('/pedidos', [OrderController::class, 'store']);
    Route::get('/pedidos/{pedido}', [OrderController::class, 'show']);
    Route::get('/pedidos/{pedido}/historico', [OrderController::class, 'history']);
    Route::patch('/pedidos/{pedido}/status', [OrderController::class, 'updateStatus']);
    Route::post('/pedidos/{pedido}/cancelamento', [OrderController::class, 'cancel']);
});
