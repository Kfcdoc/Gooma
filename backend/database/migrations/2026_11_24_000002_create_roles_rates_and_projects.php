<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colecciones e índices del modelo de datos de la fase 2.
 *
 * MongoDB es schemaless: los documentos no necesitan columnas. Esta migración
 * crea los índices que garantizan unicidad y los que aceleran las consultas
 * por cliente, gestor y vigencia de tarifa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->unique('code');
            $table->index('active');
        });

        Schema::create('role_rates', function (Blueprint $table): void {
            $table->unique(['role_id', 'currency', 'effective_from']);
            $table->index(['role_id', 'effective_from']);
        });

        Schema::create('projects', function (Blueprint $table): void {
            $table->unique('code');
            $table->index('client_id');
            $table->index('manager_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
        Schema::dropIfExists('role_rates');
        Schema::dropIfExists('roles');
    }
};
