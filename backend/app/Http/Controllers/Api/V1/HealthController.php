<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Endpoint de salud usado por Docker, el frontend y el equipo.
 *
 * Además de confirmar que Laravel responde, verifica que la conexión a
 * MongoDB esté viva y que el servidor corra como replica set, requisito
 * para usar transacciones (bitácora + movimiento financiero).
 */
class HealthController
{
    public function __invoke(): JsonResponse
    {
        $database = [
            'status' => 'ok',
            'name' => null,
            'replica_set' => null,
            'transactions' => false,
        ];

        try {
            $connection = DB::connection();
            $mongo = $connection->getDatabase();
            $mongo->command(['ping' => 1]);

            // `hello` devuelve el nombre del replica set y si el nodo es primario.
            $hello = $mongo->command(['hello' => 1])->toArray()[0] ?? [];
            $replicaSet = $hello['setName'] ?? null;

            $database['name'] = $mongo->getDatabaseName();
            $database['replica_set'] = $replicaSet;
            $database['transactions'] = is_string($replicaSet) && $replicaSet !== '';
        } catch (Throwable $exception) {
            $database = [
                'status' => 'error',
                'name' => config('database.connections.mongodb.database'),
                'replica_set' => null,
                'transactions' => false,
                'message' => config('app.debug')
                    ? $exception->getMessage()
                    : 'No fue posible conectar con MongoDB.',
            ];
        }

        $healthy = $database['status'] === 'ok';

        return response()->json([
            'app' => config('app.name'),
            'version' => '0.1.0',
            'status' => $healthy ? 'ok' : 'degraded',
            'environment' => config('app.env'),
            'timestamp' => now()->toIso8601String(),
            'database' => $database,
        ], $healthy ? 200 : 503);
    }
}
