<?php

use Illuminate\Support\Facades\Route;

/*
| GooMaps es una API + SPA. La raíz del backend solo informa que el
| servicio está en línea; la interfaz vive en el contenedor de React.
*/
Route::get('/', fn () => response()->json([
    'name' => config('app.name'),
    'type' => 'API REST',
    'version' => 'v1',
    'health' => url('/api/v1/health'),
    'frontend' => env('FRONTEND_URL', 'http://localhost:5173'),
]));
