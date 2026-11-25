<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutAllController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API de GooMaps (v1)
|--------------------------------------------------------------------------
| Autenticación por token Bearer de Sanctum. Todos los módulos de negocio
| se agregan en las fases 3 a 6.
*/

Route::prefix('v1')->group(function (): void {
    // Diagnóstico de infraestructura: aplicación + conexión a MongoDB.
    Route::get('/health', HealthController::class)->name('api.v1.health');

    // CU-01 Iniciar sesión (limitado para frenar fuerza bruta).
    Route::post('/auth/login', LoginController::class)
        ->middleware('throttle:6,1')
        ->name('api.v1.auth.login');

    Route::middleware(['auth:sanctum', 'active'])->group(function (): void {
        Route::post('/auth/logout', LogoutController::class)->name('api.v1.auth.logout');
        Route::post('/auth/logout-all', LogoutAllController::class)->name('api.v1.auth.logout-all');
        Route::get('/auth/me', MeController::class)->name('api.v1.auth.me');

        // CU-02 Gestionar usuarios y roles (solo administrador).
        Route::middleware('role:admin')->group(function (): void {
            Route::apiResource('users', UserController::class);
        });
    });
});
