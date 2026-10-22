<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_pode_criar_produto(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $response = $this->postJson('/api/produtos', [
            'name' => 'Camiseta',
            'description' => 'Camiseta de algodão',
            'price' => 59.90,
            'active' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('name', 'Camiseta')
            ->assertJsonPath('price', '59.90');

        $this->assertDatabaseHas('products', ['name' => 'Camiseta']);
    }

    public function test_cliente_nao_pode_criar_produto(): void
    {
        Sanctum::actingAs(User::factory()->cliente()->create());

        $this->postJson('/api/produtos', [
            'name' => 'Camiseta',
            'price' => 59.90,
        ])->assertForbidden();
    }

    public function test_usuario_autenticado_pode_listar_e_exibir_produtos(): void
    {
        $product = Product::factory()->create(['price' => 59.90]);
        Sanctum::actingAs(User::factory()->cliente()->create());

        $this->getJson('/api/produtos')
            ->assertOk()
            ->assertJsonPath('data.0.id', $product->id)
            ->assertJsonStructure(['data', 'links', 'meta']);

        $this->getJson("/api/produtos/{$product->id}")
            ->assertOk()
            ->assertJsonPath('id', $product->id);
    }

    public function test_admin_pode_atualizar_e_excluir_produto(): void
    {
        $product = Product::factory()->create();
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->patchJson("/api/produtos/{$product->id}", [
            'name' => 'Camiseta Atualizada',
            'price' => 79.90,
            'active' => false,
        ])->assertOk()
            ->assertJsonPath('name', 'Camiseta Atualizada')
            ->assertJsonPath('active', false);

        $this->deleteJson("/api/produtos/{$product->id}")->assertNoContent();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_validacao_de_produto(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->postJson('/api/produtos', ['name' => '', 'price' => 0])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'price']);
    }
}
