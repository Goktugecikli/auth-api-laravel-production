<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AppointmentController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->prefix('appointments')->group(function () {
    Route::get('/', [AppointmentController::class, 'index']);           // liste
    Route::post('/', [AppointmentController::class, 'store']);          // oluştur

    Route::patch('{appointment}/cancel', [AppointmentController::class, 'cancel']);   // iptal
    Route::patch('{appointment}/complete', [AppointmentController::class, 'complete']); // tamamla

    Route::put('{appointment}', [AppointmentController::class, 'update']);            // güncelle
    Route::delete('{appointment}', [AppointmentController::class, 'destroy']);        // sil
});
