<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutAllController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\ProjectTechnologyController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\RoleRateController;
use App\Http\Controllers\Api\V1\TeamMemberController;
use App\Http\Controllers\Api\V1\TechnologyController;
use App\Http\Controllers\Api\V1\TimeEntryController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API de GooMaps (v1)
|--------------------------------------------------------------------------
| Autenticación por token Bearer de Sanctum. La fase 3 agrega clientes,
| proyectos, equipo, tarifas vigentes, tecnologías y horas. Los movimientos
| financieros y reportes llegan en las fases 4 y 5.
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

        // Catálogos: lectura para todo usuario autenticado.
        Route::get('/roles', [RoleController::class, 'index']);
        Route::get('/roles/{role}', [RoleController::class, 'show']);
        Route::get('/roles/{role}/rates', [RoleRateController::class, 'index']);
        Route::get('/rates/vigente', [RoleRateController::class, 'current']);
        Route::get('/technologies', [TechnologyController::class, 'index']);
        Route::get('/technologies/{technology}', [TechnologyController::class, 'show']);

        // Clientes y proyectos: lectura con alcance por rol (RN-20/RN-21).
        Route::get('/clients', [ClientController::class, 'index']);
        Route::get('/clients/{client}', [ClientController::class, 'show']);
        Route::get('/projects', [ProjectController::class, 'index']);
        Route::get('/projects/{project}', [ProjectController::class, 'show']);

        // CU-04 a CU-07: operaciones de gestor/administrador.
        Route::middleware('role:admin,gestor')->group(function (): void {
            Route::post('/clients', [ClientController::class, 'store']);
            Route::put('/clients/{client}', [ClientController::class, 'update']);
            Route::delete('/clients/{client}', [ClientController::class, 'destroy']);

            Route::post('/projects', [ProjectController::class, 'store']);
            Route::put('/projects/{project}', [ProjectController::class, 'update']);
            Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);

            Route::get('/projects/{project}/team', [TeamMemberController::class, 'index']);
            Route::post('/projects/{project}/team', [TeamMemberController::class, 'store']);
            Route::get('/projects/{project}/team/{teamMember}', [TeamMemberController::class, 'show']);
            Route::put('/projects/{project}/team/{teamMember}', [TeamMemberController::class, 'update']);
            Route::delete('/projects/{project}/team/{teamMember}', [TeamMemberController::class, 'destroy']);

            Route::get('/projects/{project}/hours', [TimeEntryController::class, 'index']);
            Route::post('/projects/{project}/hours', [TimeEntryController::class, 'store']);
            Route::get('/projects/{project}/hours/{timeEntry}', [TimeEntryController::class, 'show']);
            Route::put('/projects/{project}/hours/{timeEntry}', [TimeEntryController::class, 'update']);
            Route::delete('/projects/{project}/hours/{timeEntry}', [TimeEntryController::class, 'destroy']);

            Route::get('/projects/{project}/technologies', [ProjectTechnologyController::class, 'index']);
            Route::post('/projects/{project}/technologies', [ProjectTechnologyController::class, 'store']);
            Route::delete(
                '/projects/{project}/technologies/{technology}',
                [ProjectTechnologyController::class, 'destroy'],
            );
        });

        // CU-02/CU-03: administración de usuarios y catálogos.
        Route::middleware('role:admin')->group(function (): void {
            Route::apiResource('users', UserController::class);

            Route::post('/roles', [RoleController::class, 'store']);
            Route::put('/roles/{role}', [RoleController::class, 'update']);
            Route::delete('/roles/{role}', [RoleController::class, 'destroy']);
            Route::post('/roles/{role}/rates', [RoleRateController::class, 'store']);

            Route::post('/technologies', [TechnologyController::class, 'store']);
            Route::put('/technologies/{technology}', [TechnologyController::class, 'update']);
            Route::delete('/technologies/{technology}', [TechnologyController::class, 'destroy']);
        });
    });
});
