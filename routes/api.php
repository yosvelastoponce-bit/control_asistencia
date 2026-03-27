<?php
// ══════════════════════════════════════════════════════════════════════════
// ARCHIVO: routes/api.ph
// ══════════════════════════════════════════════════════════════════════════
 
use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\MobileAttendanceController;
use App\Http\Controllers\Api\MobileStudentController;
use Illuminate\Support\Facades\Route;
 
Route::prefix('mobile')->group(function () {
 
    // Login — devuelve token
    Route::post('/login', [MobileAuthController::class, 'login']);
 
    // Rutas protegidas con Sanctum
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/students',   [MobileStudentController::class, 'index']);
        Route::post('/attendance',[MobileAttendanceController::class, 'store']);
        Route::post('/sync',      [MobileAttendanceController::class, 'bulkStore']);
    });
 
});