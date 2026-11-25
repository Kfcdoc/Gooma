<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Blindaje: aunque el contenedor inyecte DB_DATABASE=goomaps, las
        // pruebas siempre usan su propia base y jamás tocan datos de desarrollo.
        config([
            'database.default' => 'mongodb',
            'database.connections.mongodb.database' => 'goomaps_test',
        ]);
        DB::purge();

        $this->ensureUsingTestDatabase();
        $this->clearMongoCollections();
    }

    /**
     * Las pruebas NUNCA deben tocar la base de datos de desarrollo.
     * El nombre debe terminar en `_test` (phpunit.xml usa goomaps_test).
     */
    private function ensureUsingTestDatabase(): void
    {
        $database = (string) config('database.connections.mongodb.database');

        if (! str_ends_with($database, '_test')) {
            throw new RuntimeException(
                "La base de pruebas debe terminar en _test; se intentó usar «{$database}».",
            );
        }
    }

    /**
     * MongoDB no usa transacciones de esquema: se vacían las colecciones entre
     * pruebas. Conservar los índices evita recrear la base 48 veces por suite,
     * lo que en este entorno llegó a reiniciar WiredTiger.
     */
    private function clearMongoCollections(): void
    {
        $database = DB::connection()->getDatabase();

        foreach ($database->listCollectionNames() as $name) {
            if (! str_starts_with($name, 'system.')) {
                $database->selectCollection($name)->deleteMany([]);
            }
        }
    }
}
