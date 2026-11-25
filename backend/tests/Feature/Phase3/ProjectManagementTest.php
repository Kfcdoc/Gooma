<?php

namespace Tests\Feature\Phase3;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectTechnology;
use App\Models\Role;
use App\Models\RoleRate;
use App\Models\TeamMember;
use App\Models\TimeEntry;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectManagementTest extends TestCase
{
    public function test_el_gestor_crea_proyectos_y_queda_como_responsable_rn01(): void
    {
        $this->seed();
        $cliente = Client::where('tax_id', 'CVD010101AB1')->firstOrFail();
        $gestor = User::where('email', 'gestor@goomaps.local')->firstOrFail();
        Sanctum::actingAs($gestor);

        $response = $this->postJson('/api/v1/projects', [
            'name' => 'Proyecto nuevo',
            'client_id' => $cliente->getKey(),
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-31',
        ])->assertCreated()
            ->assertJsonPath('data.name', 'Proyecto nuevo')
            ->assertJsonPath('data.manager_id', $gestor->getKey())
            ->assertJsonPath('data.totals.planned_total_cents', 0);

        $project = Project::whereKey($response->json('data.id'))->firstOrFail();
        $this->assertSame($gestor->getKey(), $project->manager_id);
        $this->assertSame('MXN', $project->base_currency);
        $this->assertNotNull($project->code);
    }

    public function test_el_gestor_no_puede_reasignar_el_proyecto_a_otro_gestor(): void
    {
        $this->seed();
        $gestor = User::where('email', 'gestor@goomaps.local')->firstOrFail();
        $otroGestor = User::factory()->gestor()->create();
        $project = Project::where('code', 'GOOMAPS-2026')->firstOrFail();

        Sanctum::actingAs($gestor);

        $this->putJson("/api/v1/projects/{$project->getKey()}", [
            'name' => 'Nombre actualizado',
            'manager_id' => $otroGestor->getKey(),
        ])->assertOk()
            ->assertJsonPath('data.name', 'Nombre actualizado')
            ->assertJsonPath('data.manager_id', $gestor->getKey());

        $this->assertSame($gestor->getKey(), $project->fresh()->manager_id);
    }

    public function test_el_cliente_solo_ve_sus_proyectos_y_totales_agregados_rn21(): void
    {
        $this->seed();
        $cliente = User::where('email', 'cliente@goomaps.local')->firstOrFail();
        $project = Project::where('code', 'GOOMAPS-2026')->firstOrFail();
        Sanctum::actingAs($cliente);

        $this->getJson('/api/v1/projects')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.totals.planned_total_cents', 34231600);

        $this->getJson("/api/v1/projects/{$project->getKey()}")
            ->assertOk()
            ->assertJsonPath('data.totals.planned_total_cents', 34231600)
            ->assertJsonMissingPath('data.budget_lines')
            ->assertJsonMissingPath('data.budget')
            ->assertJsonMissingPath('data.extras')
            ->assertJsonMissingPath('data.time_entries')
            ->assertJsonMissingPath('data.client_id')
            ->assertJsonMissingPath('data.manager_id');

        $this->postJson('/api/v1/projects', ['name' => 'Intento'])->assertForbidden();
        $this->putJson("/api/v1/projects/{$project->getKey()}", ['name' => 'Intento'])->assertForbidden();
        $this->deleteJson("/api/v1/projects/{$project->getKey()}")->assertForbidden();
    }

    public function test_el_cliente_ve_detalles_cuando_el_gestor_lo_habilita(): void
    {
        $this->seed();
        $project = Project::where('code', 'GOOMAPS-2026')->firstOrFail();
        $project->update(['show_rates_to_client' => true]);

        Sanctum::actingAs(User::where('email', 'cliente@goomaps.local')->firstOrFail());

        $this->getJson("/api/v1/projects/{$project->getKey()}")
            ->assertOk()
            ->assertJsonCount(6, 'data.budget_lines')
            ->assertJsonPath('data.show_rates_to_client', true);
    }

    public function test_el_gestor_no_ve_ni_edita_proyectos_ajenos_rn20(): void
    {
        $this->seed();
        $otroGestor = User::factory()->gestor()->create();
        $otroCliente = Client::factory()->create();
        $proyectoAjeno = Project::create([
            'code' => 'OTRO-001',
            'name' => 'Proyecto ajeno',
            'client_id' => $otroCliente->getKey(),
            'manager_id' => $otroGestor->getKey(),
            'start_date' => '2026-11-25',
            'end_date' => '2026-12-25',
        ]);

        Sanctum::actingAs(User::where('email', 'gestor@goomaps.local')->firstOrFail());

        $this->getJson('/api/v1/projects')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissing(['id' => $proyectoAjeno->getKey()]);

        $this->getJson("/api/v1/projects/{$proyectoAjeno->getKey()}")->assertForbidden();
        $this->putJson("/api/v1/projects/{$proyectoAjeno->getKey()}", ['name' => 'Intento'])->assertForbidden();
        $this->deleteJson("/api/v1/projects/{$proyectoAjeno->getKey()}")->assertForbidden();
    }

    public function test_filtros_y_validacion_de_fechas(): void
    {
        $this->seed();
        $cliente = Client::where('tax_id', 'CVD010101AB1')->firstOrFail();
        Sanctum::actingAs(User::where('email', 'gestor@goomaps.local')->firstOrFail());

        $this->getJson('/api/v1/projects?status=active')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/projects?status=closed')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson("/api/v1/projects?client_id={$cliente->getKey()}")->assertOk()->assertJsonCount(1, 'data');

        $this->postJson('/api/v1/projects', [
            'name' => 'Fechas inválidas',
            'client_id' => $cliente->getKey(),
            'start_date' => '2026-12-31',
            'end_date' => '2026-12-01',
        ])->assertStatus(422)->assertJsonValidationErrors('end_date');

        $this->postJson('/api/v1/projects', [
            'name' => 'Cliente inexistente',
            'client_id' => 'no-existe',
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-31',
        ])->assertStatus(422)->assertJsonValidationErrors('client_id');
    }

    public function test_eliminar_proyecto_limpia_equipo_horas_y_tecnologias(): void
    {
        $this->seed();
        $project = Project::where('code', 'GOOMAPS-2026')->firstOrFail();
        $role = Role::where('code', Role::CODE_BACKEND)->firstOrFail();
        $member = $project->teamMembers()->create(['name' => 'Ana', 'role_id' => $role->getKey()]);
        $rate = RoleRate::where('role_id', $role->getKey())
            ->vigente('2026-12-01')
            ->firstOrFail();

        $project->timeEntries()->create([
            'team_member_id' => $member->getKey(),
            'role_id' => $role->getKey(),
            'work_date' => '2026-12-01',
            'minutes' => 60,
            'rate_cents' => $rate->rate_cents,
            'amount_cents' => $rate->rate_cents,
        ]);

        Sanctum::actingAs(User::where('email', 'gestor@goomaps.local')->firstOrFail());

        $this->deleteJson("/api/v1/projects/{$project->getKey()}")->assertNoContent();

        $this->assertSame(0, Project::count());
        $this->assertSame(0, TeamMember::count());
        $this->assertSame(0, TimeEntry::count());
        $this->assertSame(0, ProjectTechnology::count());
    }
}
