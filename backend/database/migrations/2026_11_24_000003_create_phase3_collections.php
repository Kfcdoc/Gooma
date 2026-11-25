<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colecciones e índices añadidos en la fase 3: equipo, horas y tecnologías.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table): void {
            $table->index('project_id');
            $table->index('role_id');
            $table->index(['project_id', 'active']);
        });

        Schema::create('time_entries', function (Blueprint $table): void {
            $table->index('project_id');
            $table->index('team_member_id');
            $table->index(['project_id', 'work_date']);
        });

        Schema::create('technologies', function (Blueprint $table): void {
            $table->unique('slug');
            $table->unique('name');
            $table->index('active');
        });

        Schema::create('project_technologies', function (Blueprint $table): void {
            $table->unique(['project_id', 'technology_id']);
            $table->index('technology_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_technologies');
        Schema::dropIfExists('technologies');
        Schema::dropIfExists('time_entries');
        Schema::dropIfExists('team_members');
    }
};
