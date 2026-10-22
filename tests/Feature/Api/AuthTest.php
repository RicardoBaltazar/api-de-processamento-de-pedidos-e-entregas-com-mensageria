<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_cliente_pode_se_registrar_e_recebe_token(): void
    {
        $response = $this->postJson('/api/registro', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.email', 'maria@example.com')
            ->assertJsonPath('user.role', 'cliente')
            ->assertJsonStructure(['user' => ['id', 'name', 'email', 'role'], 'token']);

        $this->assertDatabaseHas('users', [
            'email' => 'maria@example.com',
            'role' => 'cliente',
        ]);
    }

    public function test_registro_nao_permite_definir_papel_admin(): void
    {
        $response = $this->postJson('/api/registro', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'admin',
        ]);

        $response->assertCreated()->assertJsonPath('user.role', 'cliente');
        $this->assertDatabaseHas('users', ['email' => 'maria@example.com', 'role' => 'cliente']);
    }

    public function test_login_retorna_token(): void
    {
        User::factory()->cliente()->create([
            'email' => 'maria@example.com',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'maria@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.role', 'cliente')
            ->assertJsonStructure(['user' => ['id', 'name', 'email', 'role'], 'token']);
    }

    public function test_login_com_credenciais_invalidas_retorna_erro_de_validacao(): void
    {
        User::factory()->cliente()->create([
            'email' => 'maria@example.com',
            'password' => 'password',
        ]);

        $this->postJson('/api/login', [
            'email' => 'maria@example.com',
            'password' => 'senha-errada',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_me_retorna_usuario_autenticado(): void
    {
        $user = User::factory()->cliente()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('role', 'cliente');
    }

    public function test_logout_revoga_token_atual(): void
    {
        $user = User::factory()->cliente()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)->postJson('/api/logout')->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_rotas_protegidas_exigem_autenticacao(): void
    {
        $this->getJson('/api/pedidos')->assertUnauthorized();
        $this->getJson('/api/produtos')->assertUnauthorized();
    }
}
