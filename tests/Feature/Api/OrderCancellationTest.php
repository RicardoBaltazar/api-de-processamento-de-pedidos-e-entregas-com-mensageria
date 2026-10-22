<?php

namespace Tests\Feature\Api;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cliente_cancela_proprio_pedido_criado_com_motivo(): void
    {
        $client = User::factory()->cliente()->create();
        $order = Order::factory()->for($client)->create();
        Sanctum::actingAs($client);

        $this->postJson("/api/pedidos/{$order->id}/cancelamento", [
            'reason' => 'Desisti da compra.',
        ])->assertOk()
            ->assertJsonPath('status', 'CANCELADO');

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'user_id' => $client->id,
            'from_status' => 'CRIADO',
            'to_status' => 'CANCELADO',
            'reason' => 'Desisti da compra.',
        ]);
    }

    public function test_cliente_cancela_pedido_aguardando_pagamento(): void
    {
        $client = User::factory()->cliente()->create();
        $order = Order::factory()->for($client)->withStatus(OrderStatus::AGUARDANDO_PAGAMENTO)->create();
        Sanctum::actingAs($client);

        $this->postJson("/api/pedidos/{$order->id}/cancelamento", [
            'reason' => 'Não vou pagar.',
        ])->assertOk()->assertJsonPath('status', 'CANCELADO');
    }

    public function test_motivo_e_obrigatorio(): void
    {
        $client = User::factory()->cliente()->create();
        $order = Order::factory()->for($client)->create();
        Sanctum::actingAs($client);

        $this->postJson("/api/pedidos/{$order->id}/cancelamento")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reason');
    }

    public function test_cliente_nao_pode_cancelar_pedido_pago(): void
    {
        $client = User::factory()->cliente()->create();
        $order = Order::factory()->for($client)->withStatus(OrderStatus::PAGO)->create();
        Sanctum::actingAs($client);

        $this->postJson("/api/pedidos/{$order->id}/cancelamento", [
            'reason' => 'Quero cancelar.',
        ])->assertForbidden();

        $this->assertSame(OrderStatus::PAGO, $order->fresh()->status);
    }

    public function test_admin_pode_cancelar_pedido_pago_com_motivo(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->withStatus(OrderStatus::PAGO)->create();
        Sanctum::actingAs($admin);

        $this->postJson("/api/pedidos/{$order->id}/cancelamento", [
            'reason' => 'Estorno aprovado.',
        ])->assertOk()->assertJsonPath('status', 'CANCELADO');

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => 'PAGO',
            'to_status' => 'CANCELADO',
            'reason' => 'Estorno aprovado.',
        ]);
    }

    public function test_pedido_entregue_nao_pode_ser_cancelado(): void
    {
        $order = Order::factory()->withStatus(OrderStatus::ENTREGUE)->create();
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->postJson("/api/pedidos/{$order->id}/cancelamento", [
            'reason' => 'Tentativa de cancelamento.',
        ])->assertStatus(409);
    }

    public function test_pedido_cancelado_nao_pode_ser_cancelado_novamente(): void
    {
        $order = Order::factory()->withStatus(OrderStatus::CANCELADO)->create();
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->postJson("/api/pedidos/{$order->id}/cancelamento", [
            'reason' => 'Tentativa de cancelamento.',
        ])->assertStatus(409);
    }

    public function test_cliente_nao_pode_cancelar_pedido_de_outro_cliente(): void
    {
        $client = User::factory()->cliente()->create();
        $order = Order::factory()->create();
        Sanctum::actingAs($client);

        $this->postJson("/api/pedidos/{$order->id}/cancelamento", [
            'reason' => 'Tentativa de cancelamento.',
        ])->assertForbidden();
    }
}
