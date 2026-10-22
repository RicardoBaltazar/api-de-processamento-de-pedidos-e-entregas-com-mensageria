<?php

namespace Tests\Feature\Api;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_avanca_status_e_registra_historico(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->create();
        Sanctum::actingAs($admin);

        $this->patchJson("/api/pedidos/{$order->id}/status", [
            'status' => OrderStatus::AGUARDANDO_PAGAMENTO->value,
            'reason' => 'Pedido liberado para pagamento.',
        ])->assertOk()
            ->assertJsonPath('status', 'AGUARDANDO_PAGAMENTO');

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'user_id' => $admin->id,
            'from_status' => 'CRIADO',
            'to_status' => 'AGUARDANDO_PAGAMENTO',
            'reason' => 'Pedido liberado para pagamento.',
        ]);
    }

    public function test_admin_pode_percorrer_toda_a_sequencia(): void
    {
        $order = Order::factory()->create();
        Sanctum::actingAs(User::factory()->admin()->create());

        foreach ([
            OrderStatus::AGUARDANDO_PAGAMENTO,
            OrderStatus::PAGO,
            OrderStatus::EM_PREPARACAO,
            OrderStatus::ENVIADO,
            OrderStatus::ENTREGUE,
        ] as $status) {
            $this->patchJson("/api/pedidos/{$order->id}/status", [
                'status' => $status->value,
            ])->assertOk()->assertJsonPath('status', $status->value);
        }

        $this->assertDatabaseCount('order_status_histories', 5);
    }

    public function test_nao_permite_pular_etapas(): void
    {
        $order = Order::factory()->create();
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->patchJson("/api/pedidos/{$order->id}/status", [
            'status' => OrderStatus::PAGO->value,
        ])->assertStatus(409);
    }

    public function test_nao_permite_retroceder_status(): void
    {
        $order = Order::factory()->withStatus(OrderStatus::PAGO)->create();
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->patchJson("/api/pedidos/{$order->id}/status", [
            'status' => OrderStatus::AGUARDANDO_PAGAMENTO->value,
        ])->assertStatus(409);
    }

    public function test_cliente_nao_pode_avancar_status(): void
    {
        $client = User::factory()->cliente()->create();
        $order = Order::factory()->for($client)->create();
        Sanctum::actingAs($client);

        $this->patchJson("/api/pedidos/{$order->id}/status", [
            'status' => OrderStatus::AGUARDANDO_PAGAMENTO->value,
        ])->assertForbidden();
    }

    public function test_nao_permite_cancelar_via_endpoint_de_status(): void
    {
        $order = Order::factory()->create();
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->patchJson("/api/pedidos/{$order->id}/status", [
            'status' => OrderStatus::CANCELADO->value,
        ])->assertUnprocessable()->assertJsonValidationErrors('status');
    }

    public function test_atualizacao_com_status_desatualizado_retorna_conflito(): void
    {
        $order = Order::factory()->create();
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->patchJson("/api/pedidos/{$order->id}/status", [
            'status' => OrderStatus::AGUARDANDO_PAGAMENTO->value,
        ])->assertOk();

        $this->patchJson("/api/pedidos/{$order->id}/status", [
            'status' => OrderStatus::AGUARDANDO_PAGAMENTO->value,
        ])->assertStatus(409);
    }

    public function test_historico_retorna_alteracoes_em_ordem(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->create();
        Sanctum::actingAs($admin);

        $this->patchJson("/api/pedidos/{$order->id}/status", [
            'status' => OrderStatus::AGUARDANDO_PAGAMENTO->value,
        ])->assertOk();

        $this->getJson("/api/pedidos/{$order->id}/historico")
            ->assertOk()
            ->assertJsonPath('0.from_status', 'CRIADO')
            ->assertJsonPath('0.to_status', 'AGUARDANDO_PAGAMENTO')
            ->assertJsonPath('0.actor.id', $admin->id);
    }
}
