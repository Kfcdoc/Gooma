<?php

use App\Http\Controllers\Api\V1\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API de GooMaps (v1)
|--------------------------------------------------------------------------
| Todas las rutas se versionan bajo /api/v1 para poder evolucionar la API
| sin romper a los clientes existentes. El resto de módulos se agrega en
| las siguientes fases del proyecto.
*/

Route::prefix('v1')->group(function (): void {
    // Diagnóstico de infraestructura: aplicación + conexión a MongoDB.
    Route::get('/health', HealthController::class)->name('api.v1.health');
});
