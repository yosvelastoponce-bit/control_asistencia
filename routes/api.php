<?php

use App\Http\Controllers\Api\MobileController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile')->group(function () {
    Route::post('/login', [MobileController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [MobileController::class, 'logout']);
        Route::get('/bootstrap', [MobileController::class, 'bootstrap']);
        Route::post('/attendance', [MobileController::class, 'attendance']);
    });
});
