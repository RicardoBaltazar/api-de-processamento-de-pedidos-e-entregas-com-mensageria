<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->product = Product::factory()->create([
            'name' => 'Camiseta',
            'price' => 59.90,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function orderPayload(array $overrides = []): array
    {
        return array_merge([
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 2],
            ],
            'postal_code' => '01310100',
            'street' => 'Avenida Paulista',
            'number' => '1000',
            'complement' => 'Apto 12',
            'neighborhood' => 'Bela Vista',
            'city' => 'São Paulo',
            'state' => 'SP',
        ], $overrides);
    }

    public function test_cliente_cria_pedido_para_si_com_total_calculado(): void
    {
        $client = User::factory()->cliente()->create();
        Sanctum::actingAs($client);

        $response = $this->postJson('/api/pedidos', $this->orderPayload());

        $response->assertCreated()
            ->assertJsonPath('customer_id', $client->id)
            ->assertJsonPath('status', 'CRIADO')
            ->assertJsonPath('total', '119.80')
            ->assertJsonPath('items.0.product_name', 'Camiseta')
            ->assertJsonPath('items.0.unit_price', '59.90')
            ->assertJsonPath('items.0.quantity', 2)
            ->assertJsonPath('items.0.subtotal', '119.80')
            ->assertJsonPath('address.city', 'São Paulo');

        $orderId = $response->json('id');

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'user_id' => $client->id,
            'status' => 'CRIADO',
            'total' => 119.80,
        ]);

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $orderId,
            'from_status' => null,
            'to_status' => 'CRIADO',
            'user_id' => $client->id,
        ]);
    }

    public function test_cliente_nao_pode_criar_pedido_para_outro_cliente(): void
    {
        $client = User::factory()->cliente()->create();
        $other = User::factory()->cliente()->create();
        Sanctum::actingAs($client);

        $this->postJson('/api/pedidos', $this->orderPayload(['customer_id' => $other->id]))
            ->assertForbidden();
    }

    public function test_admin_pode_criar_pedido_para_cliente(): void
    {
        $admin = User::factory()->admin()->create();
        $client = User::factory()->cliente()->create();
        Sanctum::actingAs($admin);

        $this->postJson('/api/pedidos', $this->orderPayload(['customer_id' => $client->id]))
            ->assertCreated()
            ->assertJsonPath('customer_id', $client->id);
    }

    public function test_nao_permite_produto_inativo(): void
    {
        $inactiveProduct = Product::factory()->inactive()->create();
        Sanctum::actingAs(User::factory()->cliente()->create());

        $this->postJson('/api/pedidos', $this->orderPayload([
            'items' => [['product_id' => $inactiveProduct->id, 'quantity' => 1]],
        ]))->assertUnprocessable()->assertJsonValidationErrors('items');
    }

    public function test_validacao_dos_itens_do_pedido(): void
    {
        Sanctum::actingAs(User::factory()->cliente()->create());

        $this->postJson('/api/pedidos', $this->orderPayload([
            'items' => [['product_id' => $this->product->id, 'quantity' => 0]],
        ]))->assertUnprocessable()->assertJsonValidationErrors('items.0.quantity');

        $this->postJson('/api/pedidos', $this->orderPayload([
            'items' => [],
        ]))->assertUnprocessable()->assertJsonValidationErrors('items');
    }

    public function test_cliente_lista_apenas_os_proprios_pedidos(): void
    {
        $client = User::factory()->cliente()->create();
        $other = User::factory()->cliente()->create();
        Order::factory()->for($client)->create();
        Order::factory()->for($other)->create();

        Sanctum::actingAs($client);

        $this->getJson('/api/pedidos')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.customer_id', $client->id)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_admin_lista_todos_os_pedidos(): void
    {
        Order::factory()->count(2)->create();
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson('/api/pedidos')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);
    }

    public function test_cliente_nao_pode_ver_pedido_de_outro_cliente(): void
    {
        $client = User::factory()->cliente()->create();
        $other = User::factory()->cliente()->create();
        $order = Order::factory()->for($other)->create();

        Sanctum::actingAs($client);

        $this->getJson("/api/pedidos/{$order->id}")->assertForbidden();
    }
}
