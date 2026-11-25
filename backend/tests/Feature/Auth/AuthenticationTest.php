<?php

namespace Tests\Feature\Auth;

use App\Models\PersonalAccessToken;
use App\Models\User;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    public function test_un_usuario_activo_puede_iniciar_sesion_y_recibe_un_token(): void
    {
        $user = User::factory()->admin()->create([
            'email' => 'admin@goomaps.local',
            'password' => 'secret123',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'ADMIN@goomaps.local',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.role', User::ROLE_ADMIN)
            ->assertJsonMissingPath('user.password')
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email', 'role', 'active'],
            ]);

        $plainTextToken = $response->json('token');

        $this->assertIsString($plainTextToken);
        $this->assertSame(1, PersonalAccessToken::count());
        // En la base solo se guarda el hash, nunca el token en claro.
        $this->assertNotSame($plainTextToken, PersonalAccessToken::first()->token);
    }

    public function test_las_credenciales_invalidas_se_rechazan(): void
    {
        User::factory()->create(['email' => 'gestor@goomaps.local', 'password' => 'secret123']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'gestor@goomaps.local',
            'password' => 'incorrecta',
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_un_usuario_desactivado_no_puede_iniciar_sesion(): void
    {
        User::factory()->inactive()->create([
            'email' => 'inactivo@goomaps.local',
            'password' => 'secret123',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'inactivo@goomaps.local',
            'password' => 'secret123',
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_me_devuelve_el_usuario_autenticado(): void
    {
        $cliente = User::factory()->cliente()->create();

        $this->withToken($this->loginToken($cliente->email))
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', $cliente->email)
            ->assertJsonPath('data.role', User::ROLE_CLIENTE)
            ->assertJsonPath('data.client.id', $cliente->client_id);
    }

    public function test_logout_revoca_el_token_actual(): void
    {
        $gestor = User::factory()->gestor()->create();
        $token = $this->loginToken($gestor->email);

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertNoContent();
        $this->forgetAuthGuards();

        $this->withToken($token)->getJson('/api/v1/auth/me')->assertUnauthorized();
        $this->assertSame(0, PersonalAccessToken::count());
    }

    public function test_logout_all_revoca_todas_las_sesiones(): void
    {
        $gestor = User::factory()->gestor()->create();
        $primerToken = $this->loginToken($gestor->email);
        $segundoToken = $this->loginToken($gestor->email);

        $this->withToken($primerToken)->postJson('/api/v1/auth/logout-all')->assertNoContent();
        $this->forgetAuthGuards();

        $this->withToken($primerToken)->getJson('/api/v1/auth/me')->assertUnauthorized();
        $this->withToken($segundoToken)->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    /**
     * En pruebas, el mismo proceso atiende varias peticiones y el guard de
     * Sanctum conserva al usuario resuelto. En HTTP real cada petición es
     * independiente; aquí hay que olvidar los guards para simularlo.
     */
    private function forgetAuthGuards(): void
    {
        $this->app['auth']->forgetGuards();
    }

    private function loginToken(string $email, string $password = 'password'): string
    {
        return $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => $password,
        ])->assertOk()->json('token');
    }
}
