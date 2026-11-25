<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectTechnology;
use App\Models\Role;
use App\Models\RoleRate;
use App\Models\Technology;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    public function test_crea_los_tres_roles_y_es_idempotente(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(3, User::count());
        $this->assertSame(1, Client::count());

        $admin = User::where('email', 'admin@goomaps.local')->firstOrFail();
        $gestor = User::where('email', 'gestor@goomaps.local')->firstOrFail();
        $cliente = User::where('email', 'cliente@goomaps.local')->firstOrFail();

        $this->assertSame(User::ROLE_ADMIN, $admin->role);
        $this->assertSame(User::ROLE_GESTOR, $gestor->role);
        $this->assertSame(User::ROLE_CLIENTE, $cliente->role);
        $this->assertNotNull($cliente->client_id);
        $this->assertTrue(Hash::check('password', $admin->password));
    }

    public function test_el_catalogo_y_el_proyecto_demo_cuadran_con_la_seccion_1_7(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(6, Role::count());
        $this->assertSame(6, RoleRate::count());
        $this->assertSame(1, Project::count());

        $project = Project::where('code', 'GOOMAPS-2026')->firstOrFail();

        // RN-05: catálogo y asociaciones del proyecto demo, sin duplicados.
        $this->assertSame(6, Technology::count());
        $this->assertSame(6, ProjectTechnology::where('project_id', $project->getKey())->count());

        $gestor = User::where('email', 'gestor@goomaps.local')->firstOrFail();
        $cliente = User::where('email', 'cliente@goomaps.local')->firstOrFail();

        // RN-01: todo proyecto tiene cliente y gestor responsable.
        $this->assertSame($gestor->getKey(), $project->manager_id);
        $this->assertSame($cliente->client_id, $project->client_id);

        // RN-20: visibilidad del proyecto demo.
        $this->assertTrue($project->isVisibleBy($gestor));
        $this->assertTrue($project->isVisibleBy($cliente));
        $this->assertFalse($project->isVisibleBy(User::factory()->gestor()->create()));

        // RN-03/RN-04: las 6 líneas congelan horas × tarifa vigente.
        $this->assertCount(6, $project->budget_lines);
        $baseCostCents = array_sum(array_column($project->budget_lines, 'amount_cents'));
        $this->assertSame(22700000, $baseCostCents);

        // RN-15 y decisión del equipo: margen del 15% sobre costo base.
        $budget = $project->budget;
        $this->assertSame(22700000, $budget['base_cost_cents']);
        $this->assertSame(6810000, $budget['extras_cents']);
        $this->assertSame(29510000, $budget['subtotal_cents']);
        $this->assertSame(4721600, $budget['tax_cents']);
        $this->assertSame(34231600, $budget['total_cents']);
        $this->assertSame(34231600, $project->budget_initial_cents);
        $this->assertSame(32915000, $budget['total_without_optional_cents']);

        $extras = collect($project->extras)->keyBy('code');
        $this->assertSame(15, $extras->get('margen_ganancia')['percent']);
        $this->assertSame('necesario', $extras->get('margen_ganancia')['type']);
        $this->assertTrue($extras->get('riesgo_tecnologico')['active']);
        $this->assertSame('opcional', $extras->get('riesgo_tecnologico')['type']);
    }
}
