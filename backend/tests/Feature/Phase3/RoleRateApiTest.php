<?php

namespace Tests\Feature\Phase3;

use App\Models\Role;
use App\Models\RoleRate;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoleRateApiTest extends TestCase
{
    public function test_admin_registra_tarifa_nueva_y_cierra_la_anterior_rn04(): void
    {
        $this->seed();
        $role = Role::where('code', Role::CODE_BACKEND)->firstOrFail();
        $anterior = RoleRate::where('role_id', $role->getKey())
            ->vigente('2026-11-25')
            ->firstOrFail();

        Sanctum::actingAs(User::where('email', 'admin@goomaps.local')->firstOrFail());

        $this->postJson("/api/v1/roles/{$role->getKey()}/rates", [
            'rate' => 500,
            'effective_from' => '2027-01-01',
            'notes' => 'Tarifa 2027',
        ])->assertCreated()
            ->assertJsonPath('data.rate_cents', 50000)
            ->assertJsonPath('data.rate', '500.00')
            ->assertJsonPath('data.effective_from', '2027-01-01');

        $this->assertSame(40000, $anterior->fresh()->rate_cents);
        $this->assertSame('2027-01-01', $anterior->fresh()->effective_to);

        $this->getJson('/api/v1/rates/vigente?role='.Role::CODE_BACKEND.'&date=2026-11-25')
            ->assertOk()
            ->assertJsonPath('data.rate_cents', 40000);

        // El último día antes del cambio sigue usando la tarifa anterior.
        $this->getJson('/api/v1/rates/vigente?role='.Role::CODE_BACKEND.'&date=2026-12-31')
            ->assertOk()
            ->assertJsonPath('data.rate_cents', 40000);

        $this->getJson('/api/v1/rates/vigente?role='.Role::CODE_BACKEND.'&date=2027-01-01')
            ->assertOk()
            ->assertJsonPath('data.rate_cents', 50000);

        $this->getJson("/api/v1/roles/{$role->getKey()}/rates")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_no_se_acepta_una_tarifa_que_se_traslapa(): void
    {
        $this->seed();
        $role = Role::where('code', Role::CODE_BACKEND)->firstOrFail();
        Sanctum::actingAs(User::where('email', 'admin@goomaps.local')->firstOrFail());

        $this->postJson("/api/v1/roles/{$role->getKey()}/rates", [
            'rate' => 600,
            'effective_from' => '2026-01-01',
        ])->assertStatus(422)->assertJsonValidationErrors('effective_from');
    }

    public function test_el_gestor_no_puede_modificar_catalogos(): void
    {
        $this->seed();
        $role = Role::where('code', Role::CODE_BACKEND)->firstOrFail();
        Sanctum::actingAs(User::where('email', 'gestor@goomaps.local')->firstOrFail());

        $this->postJson("/api/v1/roles/{$role->getKey()}/rates", [
            'rate' => 999,
            'effective_from' => '2027-01-01',
        ])->assertForbidden();

        $this->postJson('/api/v1/roles', ['code' => 'nuevo', 'name' => 'Nuevo'])->assertForbidden();
    }

    public function test_la_consulta_de_tarifa_vigente_valida_rol_y_fecha(): void
    {
        $this->seed();
        Sanctum::actingAs(User::where('email', 'gestor@goomaps.local')->firstOrFail());

        $this->getJson('/api/v1/rates/vigente?role=inexistente')
            ->assertStatus(422)
            ->assertJsonValidationErrors('role');

        $this->getJson('/api/v1/rates/vigente?role='.Role::CODE_BACKEND.'&date=2026-13-40')
            ->assertStatus(422)
            ->assertJsonValidationErrors('date');
    }
}
