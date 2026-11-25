<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MongoDB es schemaless: los documentos no necesitan columnas. Esta migración
 * crea las colecciones y sus índices, que sí son necesarios para rendimiento
 * y para garantizar unicidad (correo y token).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->unique('email');
            $table->index('role');
            $table->index('client_id');
        });

        Schema::create('clients', function (Blueprint $table): void {
            // Sparse: permite clientes sin RFC sin romper la unicidad.
            $table->unique('tax_id', null, null, ['sparse' => true]);
            $table->index('name');
        });

        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->unique('token');
            $table->index(['tokenable_type', 'tokenable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('users');
    }
};
