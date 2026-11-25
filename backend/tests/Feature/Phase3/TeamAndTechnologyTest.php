<?php

namespace Tests\Feature\Phase3;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectTechnology;
use App\Models\Role;
use App\Models\RoleRate;
use App\Models\Technology;
use App\Models\TimeEntry;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TeamAndTechnologyTest extends TestCase
{
    public function test_el_gestor_asigna_y_edita_personas_del_equipo(): void
    {
        $this->seed();
        $project = Project::where('code', 'GOOMAPS-2026')->firstOrFail();
        $role = Role::where('code', Role::CODE_BACKEND)->firstOrFail();
        Sanctum::actingAs(User::where('email', 'gestor@goomaps.local')->firstOrFail());

        $created = $this->postJson("/api/v1/projects/{$project->getKey()}/team", [
            'name' => 'Ana López',
            'role_id' => $role->getKey(),
            'email' => 'ana@example.com',
        ])->assertCreated()
            ->assertJsonPath('data.name', 'Ana López')
            ->assertJsonPath('data.role.code', Role::CODE_BACKEND);

        $id = $created->json('data.id');

        $this->getJson("/api/v1/projects/{$project->getKey()}/team")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->putJson("/api/v1/projects/{$project->getKey()}/team/{$id}", [
            'name' => 'Ana Martínez',
            'active' => false,
        ])->assertOk()
            ->assertJsonPath('data.name', 'Ana Martínez')
            ->assertJsonPath('data.active', false);

        $this->deleteJson("/api/v1/projects/{$project->getKey()}/team/{$id}")->assertNoContent();
        $this->assertSame(0, $project->teamMembers()->count());
    }

    public function test_no_se_puede_eliminar_una_persona_con_horas_registradas(): void
    {
        $this->seed();
        $project = Project::where('code', 'GOOMAPS-2026')->firstOrFail();
        $role = Role::where('code', Role::CODE_BACKEND)->firstOrFail();
        $rate = RoleRate::where('role_id', $role->getKey())->vigente('2026-12-01')->firstOrFail();

        $member = $project->teamMembers()->create(['name' => 'Bruno', 'role_id' => $role->getKey()]);
        $project->timeEntries()->create([
            'team_member_id' => $member->getKey(),
            'role_id' => $role->getKey(),
            'work_date' => '2026-12-01',
            'minutes' => 60,
            'rate_cents' => $rate->rate_cents,
            'amount_cents' => $rate->rate_cents,
        ]);

        Sanctum::actingAs(User::where('email', 'gestor@goomaps.local')->firstOrFail());

        $this->deleteJson("/api/v1/projects/{$project->getKey()}/team/{$member->getKey()}")
            ->assertStatus(422)
            ->assertJsonValidationErrors('team_member');
    }

    public function test_solo_el_administrador_gestiona_el_catalogo_de_tecnologias(): void
    {
        $this->seed();
        Sanctum::actingAs(User::where('email', 'gestor@goomaps.local')->firstOrFail());

        $this->postJson('/api/v1/technologies', ['name' => 'Node'])->assertForbidden();

        Sanctum::actingAs(User::where('email', 'admin@goomaps.local')->firstOrFail());

        $created = $this->postJson('/api/v1/technologies', [
            'name' => 'Node JS',
            'category' => 'backend',
        ])->assertCreated()
            ->assertJsonPath('data.slug', 'node-js');

        $id = $created->json('data.id');

        $this->putJson("/api/v1/technologies/{$id}", ['category' => 'runtime'])
            ->assertOk()
            ->assertJsonPath('data.category', 'runtime');

        $this->deleteJson("/api/v1/technologies/{$id}")->assertNoContent();
    }

    public function test_proyecto_asocia_tecnologias_sin_duplicar_y_no_borra_las_usadas(): void
    {
        $this->seed();
        $project = Project::where('code', 'GOOMAPS-2026')->firstOrFail();
        $technology = Technology::create([
            'name' => 'Redis',
            'slug' => 'redis',
            'category' => 'base_de_datos',
        ]);

        Sanctum::actingAs(User::where('email', 'gestor@goomaps.local')->firstOrFail());

        $this->getJson("/api/v1/projects/{$project->getKey()}/technologies")
            ->assertOk()
            ->assertJsonCount(6, 'data');

        $this->postJson("/api/v1/projects/{$project->getKey()}/technologies", [
            'technology_id' => $technology->getKey(),
            'version' => '7',
        ])->assertCreated()->assertJsonPath('data.version', '7');

        $this->postJson("/api/v1/projects/{$project->getKey()}/technologies", [
            'technology_id' => $technology->getKey(),
            'version' => '8',
        ])->assertCreated()->assertJsonPath('data.version', '8');

        $this->assertSame(7, $project->projectTechnologies()->count());

        Sanctum::actingAs(User::where('email', 'admin@goomaps.local')->firstOrFail());
        $this->deleteJson("/api/v1/technologies/{$technology->getKey()}")
            ->assertStatus(422)
            ->assertJsonValidationErrors('technology');

        Sanctum::actingAs(User::where('email', 'gestor@goomaps.local')->firstOrFail());
        $this->deleteJson("/api/v1/projects/{$project->getKey()}/technologies/{$technology->getKey()}")
            ->assertNoContent();

        $this->assertSame(6, ProjectTechnology::where('project_id', $project->getKey())->count());
    }

    public function test_el_gestor_no_opera_equipo_de_proyectos_ajenos(): void
    {
        $this->seed();
        $otroGestor = User::factory()->gestor()->create();
        $otroProyecto = Project::create([
            'code' => 'AJENO-001',
            'name' => 'Proyecto ajeno',
            'client_id' => Client::factory()->create()->getKey(),
            'manager_id' => $otroGestor->getKey(),
            'start_date' => '2026-11-25',
            'end_date' => '2026-12-25',
        ]);

        Sanctum::actingAs(User::where('email', 'gestor@goomaps.local')->firstOrFail());

        $this->getJson("/api/v1/projects/{$otroProyecto->getKey()}/team")->assertForbidden();
        $this->postJson("/api/v1/projects/{$otroProyecto->getKey()}/team", [
            'name' => 'Intento',
            'role_id' => Role::firstOrFail()->getKey(),
        ])->assertForbidden();

        $this->assertSame(0, TimeEntry::count());
    }
}
