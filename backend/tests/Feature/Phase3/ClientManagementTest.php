<?php

namespace Tests\Feature\Phase3;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClientManagementTest extends TestCase
{
    public function test_el_gestor_puede_crear_y_editar_clientes(): void
    {
        $this->seed();
        Sanctum::actingAs(User::where('email', 'gestor@goomaps.local')->firstOrFail());

        $created = $this->postJson('/api/v1/clients', [
            'name' => 'Nueva Constructora',
            'tax_id' => 'NUE010101AB1',
            'contact_name' => 'Ana López',
        ])->assertCreated()->assertJsonPath('data.name', 'Nueva Constructora');

        $id = $created->json('data.id');

        $this->putJson("/api/v1/clients/{$id}", [
            'name' => 'Constructora Renombrada',
            'active' => false,
        ])->assertOk()
            ->assertJsonPath('data.name', 'Constructora Renombrada')
            ->assertJsonPath('data.active', false);

        $this->assertSame('NUE010101AB1', Client::whereKey($id)->firstOrFail()->tax_id);
    }

    public function test_el_cliente_solo_ve_su_registro_y_no_puede_crear(): void
    {
        $this->seed();
        $cliente = User::where('email', 'cliente@goomaps.local')->firstOrFail();
        $otroCliente = Client::factory()->create();
        Sanctum::actingAs($cliente);

        $this->getJson('/api/v1/clients')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $cliente->client_id);

        $this->getJson("/api/v1/clients/{$otroCliente->getKey()}")->assertForbidden();
        $this->postJson('/api/v1/clients', ['name' => 'Intento'])->assertForbidden();
    }

    public function test_el_gestor_ve_todos_los_clientes_aunque_no_vea_proyectos_ajenos(): void
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

        // CU-04: gestiona clientes. RN-20: solo ve sus proyectos.
        $this->getJson('/api/v1/clients')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $otroCliente->getKey()]);

        $this->getJson("/api/v1/clients/{$otroCliente->getKey()}")->assertOk();
        $this->getJson("/api/v1/projects/{$proyectoAjeno->getKey()}")->assertForbidden();
    }

    public function test_no_se_puede_eliminar_un_cliente_con_proyectos(): void
    {
        $this->seed();
        $cliente = Client::where('tax_id', 'CVD010101AB1')->firstOrFail();
        Sanctum::actingAs(User::where('email', 'admin@goomaps.local')->firstOrFail());

        $this->deleteJson("/api/v1/clients/{$cliente->getKey()}")
            ->assertStatus(422)
            ->assertJsonValidationErrors('client');
    }
}
