<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class OrderService
{
    /**
     * Cria um pedido com os itens e valores calculados pelo servidor.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, array $data): Order
    {
        return DB::transaction(function () use ($actor, $data) {
            $customerId = $this->resolveCustomerId($actor, $data);

            $requestedItems = collect($data['items']);
            $products = Product::query()
                ->whereIn('id', $requestedItems->pluck('product_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $items = [];
            $totalInCents = 0;

            foreach ($requestedItems as $requestedItem) {
                $product = $products->get($requestedItem['product_id']);

                if (! $product || ! $product->active) {
                    throw ValidationException::withMessages([
                        'items' => ['Um dos produtos informados não está disponível.'],
                    ]);
                }

                $unitPriceInCents = $this->toCents($product->price);
                $subtotalInCents = $unitPriceInCents * (int) $requestedItem['quantity'];
                $totalInCents += $subtotalInCents;

                $items[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => $this->fromCents($unitPriceInCents),
                    'quantity' => (int) $requestedItem['quantity'],
                    'subtotal' => $this->fromCents($subtotalInCents),
                ];
            }

            $order = Order::create([
                'user_id' => $customerId,
                'status' => OrderStatus::CRIADO,
                'total' => $this->fromCents($totalInCents),
                'postal_code' => $data['postal_code'],
                'street' => $data['street'],
                'number' => $data['number'],
                'complement' => $data['complement'] ?? null,
                'neighborhood' => $data['neighborhood'],
                'city' => $data['city'],
                'state' => strtoupper($data['state']),
            ]);

            $order->items()->createMany($items);

            $order->statusHistories()->create([
                'user_id' => $actor->id,
                'from_status' => null,
                'to_status' => OrderStatus::CRIADO,
                'reason' => null,
            ]);

            return $order->load('items');
        });
    }

    /**
     * Avança o pedido para o próximo status permitido.
     */
    public function transition(int $orderId, OrderStatus $target, User $actor, ?string $reason = null): Order
    {
        return DB::transaction(function () use ($orderId, $target, $actor, $reason) {
            $order = Order::query()->lockForUpdate()->findOrFail($orderId);

            if (! $actor->isAdmin()) {
                throw new AuthorizationException('Somente administradores podem alterar o status do pedido.');
            }

            if ($order->status->next() !== $target) {
                throw new ConflictHttpException('Transição de status inválida.');
            }

            $previous = $order->status;
            $order->update(['status' => $target]);

            $order->statusHistories()->create([
                'user_id' => $actor->id,
                'from_status' => $previous,
                'to_status' => $target,
                'reason' => $reason,
            ]);

            return $order->load('items');
        });
    }

    /**
     * Cancela o pedido conforme as regras de status e permissão.
     */
    public function cancel(int $orderId, User $actor, string $reason): Order
    {
        return DB::transaction(function () use ($orderId, $actor, $reason) {
            $order = Order::query()->lockForUpdate()->findOrFail($orderId);

            if (! $actor->isAdmin() && $order->user_id !== $actor->id) {
                throw new AuthorizationException('Você não pode cancelar este pedido.');
            }

            if (! $order->status->canBeCancelled()) {
                throw new ConflictHttpException('Este pedido não pode ser cancelado.');
            }

            if ($order->status->requiresAdminToCancel() && ! $actor->isAdmin()) {
                throw new AuthorizationException('Somente administradores podem cancelar pedidos pagos ou posteriores.');
            }

            $previous = $order->status;
            $order->update(['status' => OrderStatus::CANCELADO]);

            $order->statusHistories()->create([
                'user_id' => $actor->id,
                'from_status' => $previous,
                'to_status' => OrderStatus::CANCELADO,
                'reason' => $reason,
            ]);

            return $order->load('items');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveCustomerId(User $actor, array $data): int
    {
        $requestedCustomerId = isset($data['customer_id']) ? (int) $data['customer_id'] : null;

        if ($actor->isAdmin()) {
            return $requestedCustomerId ?? $actor->id;
        }

        if ($requestedCustomerId !== null && $requestedCustomerId !== $actor->id) {
            throw new AuthorizationException('Você só pode criar pedidos para si mesmo.');
        }

        return $actor->id;
    }

    private function toCents(string|int|float $value): int
    {
        return (int) round(((float) $value) * 100);
    }

    private function fromCents(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
