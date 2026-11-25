<?php

namespace Tests\Feature\Auth;

use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    public function test_un_invitado_no_puede_listar_usuarios(): void
    {
        $this->getJson('/api/v1/users')->assertUnauthorized();
    }

    public function test_un_gestor_no_puede_gestionar_usuarios(): void
    {
        Sanctum::actingAs(User::factory()->gestor()->create());

        $this->getJson('/api/v1/users')->assertForbidden();
        $this->postJson('/api/v1/users', [])->assertForbidden();
    }

    public function test_un_cliente_no_puede_gestionar_usuarios(): void
    {
        Sanctum::actingAs(User::factory()->cliente()->create());

        $this->getJson('/api/v1/users')->assertForbidden();
    }

    public function test_el_administrador_puede_listar_y_crear_usuarios(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson('/api/v1/users')->assertOk();

        $response = $this->postJson('/api/v1/users', [
            'name' => 'Nuevo Gestor',
            'email' => 'nuevo.gestor@goomaps.local',
            'password' => 'password123',
            'role' => User::ROLE_GESTOR,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.email', 'nuevo.gestor@goomaps.local')
            ->assertJsonPath('data.role', User::ROLE_GESTOR)
            ->assertJsonMissingPath('data.password');

        $creado = User::where('email', 'nuevo.gestor@goomaps.local')->firstOrFail();
        $this->assertTrue(Hash::check('password123', $creado->password));
    }

    public function test_crear_un_cliente_exige_client_id_valido(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $client = Client::factory()->create();

        $this->postJson('/api/v1/users', [
            'name' => 'Cliente sin empresa',
            'email' => 'sin.empresa@goomaps.local',
            'password' => 'password123',
            'role' => User::ROLE_CLIENTE,
        ])->assertStatus(422)->assertJsonValidationErrors('client_id');

        $this->postJson('/api/v1/users', [
            'name' => 'Cliente con empresa',
            'email' => 'con.empresa@goomaps.local',
            'password' => 'password123',
            'role' => User::ROLE_CLIENTE,
            'client_id' => $client->getKey(),
        ])->assertCreated()->assertJsonPath('data.client.id', $client->getKey());
    }

    public function test_el_administrador_no_puede_eliminarse_a_si_mismo(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $this->deleteJson("/api/v1/users/{$admin->id}")->assertStatus(422);
        $this->assertTrue(User::whereKey($admin->id)->exists());
    }

    public function test_no_se_puede_repetir_el_correo(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $existente = User::factory()->create(['email' => 'repetido@goomaps.local']);

        $this->postJson('/api/v1/users', [
            'name' => 'Duplicado',
            'email' => $existente->email,
            'password' => 'password123',
            'role' => User::ROLE_GESTOR,
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }
}
