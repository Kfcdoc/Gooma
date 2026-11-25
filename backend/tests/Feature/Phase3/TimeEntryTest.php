<?php

namespace Tests\Feature\Phase3;

use App\Models\Project;
use App\Models\Role;
use App\Models\RoleRate;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\RoleRateService;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TimeEntryTest extends TestCase
{
    public function test_registra_horas_con_tarifa_vigente_y_congela_el_snapshot_rn03_rn04(): void
    {
        $this->seed();
        $project = Project::where('code', 'GOOMAPS-2026')->firstOrFail();
        $role = Role::where('code', Role::CODE_BACKEND)->firstOrFail();
        $member = $project->teamMembers()->create(['name' => 'Ana', 'role_id' => $role->getKey()]);

        Sanctum::actingAs(User::where('email', 'gestor@goomaps.local')->firstOrFail());

        $entry = $this->postJson("/api/v1/projects/{$project->getKey()}/hours", [
            'team_member_id' => $member->getKey(),
            'work_date' => '2026-12-01',
            'hours' => 2.5,
            'description' => 'Diseño de modelo',
        ])->assertCreated()
            ->assertJsonPath('data.minutes', 150)
            ->assertJsonPath('data.hours', '2.50')
            ->assertJsonPath('data.rate_cents', 40000)
            ->assertJsonPath('data.amount_cents', 100000);

        $this->assertSame(100000, $project->fresh()->actual_cost_cents);
        $this->assertSame(100000, TimeEntry::whereKey($entry->json('data.id'))->firstOrFail()->amount_cents);

        // Un cambio del catálogo posterior NO reescribe la hora registrada.
        app(RoleRateService::class)->create($role, [
            'currency' => RoleRate::CURRENCY_MXN,
            'rate_cents' => 50000,
            'effective_from' => '2026-11-26',
        ]);

        $this->assertSame(40000, TimeEntry::whereKey($entry->json('data.id'))->firstOrFail()->rate_cents);
        $this->assertSame(100000, $project->fresh()->actual_cost_cents);

        $this->getJson("/api/v1/projects/{$project->getKey()}")
            ->assertOk()
            ->assertJsonPath('data.totals.actual_base_cost_cents', 100000)
            ->assertJsonPath('data.totals.total_hours', '2.50')
            ->assertJsonPath('data.totals.time_entries', 1);
    }

    public function test_editar_y_borrar_horas_recalcula_el_costo_real(): void
    {
        $this->seed();
        $project = Project::where('code', 'GOOMAPS-2026')->firstOrFail();
        $role = Role::where('code', Role::CODE_BACKEND)->firstOrFail();
        $member = $project->teamMembers()->create(['name' => 'Ana', 'role_id' => $role->getKey()]);

        Sanctum::actingAs(User::where('email', 'gestor@goomaps.local')->firstOrFail());

        $created = $this->postJson("/api/v1/projects/{$project->getKey()}/hours", [
            'team_member_id' => $member->getKey(),
            'work_date' => '2026-12-01',
            'hours' => 2,
        ])->assertCreated();

        $id = $created->json('data.id');
        $this->assertSame(80000, $project->fresh()->actual_cost_cents);

        $this->putJson("/api/v1/projects/{$project->getKey()}/hours/{$id}", [
            'hours' => 3,
        ])->assertOk()
            ->assertJsonPath('data.amount_cents', 120000);

        $this->assertSame(120000, $project->fresh()->actual_cost_cents);

        $this->deleteJson("/api/v1/projects/{$project->getKey()}/hours/{$id}")->assertNoContent();
        $this->assertSame(0, $project->fresh()->actual_cost_cents);
        $this->assertSame(0, TimeEntry::count());
    }

    public function test_rechaza_horas_sin_tarifa_vigente_o_en_otra_divisa(): void
    {
        $this->seed();
        $project = Project::where('code', 'GOOMAPS-2026')->firstOrFail();

        $sinTarifa = Role::create(['code' => 'sin_tarifa', 'name' => 'Sin tarifa']);
        app(RoleRateService::class)->create($sinTarifa, [
            'currency' => RoleRate::CURRENCY_MXN,
            'rate_cents' => 20000,
            'effective_from' => '2027-01-01',
        ]);
        $memberSinTarifa = $project->teamMembers()->create([
            'name' => 'Sin tarifa',
            'role_id' => $sinTarifa->getKey(),
        ]);

        $enDolares = Role::create(['code' => 'solo_usd', 'name' => 'Solo USD']);
        app(RoleRateService::class)->create($enDolares, [
            'currency' => 'USD',
            'rate_cents' => 20000,
            'effective_from' => '2026-01-01',
        ]);
        $memberEnDolares = $project->teamMembers()->create([
            'name' => 'En dólares',
            'role_id' => $enDolares->getKey(),
        ]);

        Sanctum::actingAs(User::where('email', 'gestor@goomaps.local')->firstOrFail());

        $this->postJson("/api/v1/projects/{$project->getKey()}/hours", [
            'team_member_id' => $memberSinTarifa->getKey(),
            'work_date' => '2026-12-01',
            'hours' => 1,
        ])->assertStatus(422)->assertJsonValidationErrors('work_date');

        $this->postJson("/api/v1/projects/{$project->getKey()}/hours", [
            'team_member_id' => $memberEnDolares->getKey(),
            'work_date' => '2026-12-01',
            'hours' => 1,
        ])->assertStatus(422)->assertJsonValidationErrors('work_date');
    }

    public function test_una_persona_de_otro_proyecto_no_puede_registrar_horas(): void
    {
        $this->seed();
        $proyecto = Project::where('code', 'GOOMAPS-2026')->firstOrFail();
        $otroProyecto = Project::create([
            'code' => 'OTRO-HORAS',
            'name' => 'Otro proyecto',
            'client_id' => $proyecto->client_id,
            'manager_id' => $proyecto->manager_id,
            'start_date' => '2026-11-25',
            'end_date' => '2026-12-25',
        ]);
        $role = Role::where('code', Role::CODE_BACKEND)->firstOrFail();
        $member = $proyecto->teamMembers()->create(['name' => 'Ana', 'role_id' => $role->getKey()]);

        Sanctum::actingAs(User::where('email', 'gestor@goomaps.local')->firstOrFail());

        $this->postJson("/api/v1/projects/{$otroProyecto->getKey()}/hours", [
            'team_member_id' => $member->getKey(),
            'work_date' => '2026-12-01',
            'hours' => 1,
        ])->assertStatus(422)->assertJsonValidationErrors('team_member_id');
    }

    public function test_cliente_y_visitante_no_pueden_registrar_horas(): void
    {
        $this->seed();
        $project = Project::where('code', 'GOOMAPS-2026')->firstOrFail();
        $role = Role::where('code', Role::CODE_BACKEND)->firstOrFail();
        $member = $project->teamMembers()->create(['name' => 'Ana', 'role_id' => $role->getKey()]);

        $payload = [
            'team_member_id' => $member->getKey(),
            'work_date' => '2026-12-01',
            'hours' => 1,
        ];

        Sanctum::actingAs(User::where('email', 'cliente@goomaps.local')->firstOrFail());
        $this->postJson("/api/v1/projects/{$project->getKey()}/hours", $payload)->assertForbidden();

        $this->app['auth']->forgetGuards();
        $this->postJson("/api/v1/projects/{$project->getKey()}/hours", $payload)->assertUnauthorized();
    }
}
