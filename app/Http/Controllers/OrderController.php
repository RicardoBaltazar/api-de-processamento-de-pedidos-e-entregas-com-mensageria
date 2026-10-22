<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Http\Requests\CancelOrderRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderStatusHistoryResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    #[OA\Get(
        path: '/pedidos',
        operationId: 'listarPedidos',
        summary: 'Lista pedidos',
        description: 'Clientes visualizam apenas os próprios pedidos. Administradores visualizam todos.',
        security: [['bearerAuth' => []]],
        tags: ['Pedidos'],
        responses: [
            new OA\Response(response: 200, description: 'Pedidos paginados.', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedOrders')),
            new OA\Response(response: 401, description: 'Não autenticado.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Order::class);

        $orders = Order::query()
            ->with('items')
            ->when(! $request->user()->isAdmin(), fn ($query) => $query->where('user_id', $request->user()->id))
            ->latest()
            ->paginate(15);

        return OrderResource::collection($orders);
    }

    #[OA\Post(
        path: '/pedidos',
        operationId: 'criarPedido',
        summary: 'Cria um pedido',
        security: [['bearerAuth' => []]],
        tags: ['Pedidos'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreOrderRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Pedido criado.', content: new OA\JsonContent(ref: '#/components/schemas/Order')),
            new OA\Response(response: 401, description: 'Não autenticado.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 403, description: 'Acesso negado.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'Dados inválidos.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $this->authorize('create', Order::class);

        $order = $this->orderService->create($request->user(), $request->validated());

        return response()->json(new OrderResource($order), 201);
    }

    #[OA\Get(
        path: '/pedidos/{pedido}',
        operationId: 'exibirPedido',
        summary: 'Exibe um pedido',
        security: [['bearerAuth' => []]],
        tags: ['Pedidos'],
        parameters: [
            new OA\Parameter(name: 'pedido', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Pedido encontrado.', content: new OA\JsonContent(ref: '#/components/schemas/Order')),
            new OA\Response(response: 403, description: 'Acesso negado.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'Pedido não encontrado.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function show(Order $pedido): OrderResource
    {
        $this->authorize('view', $pedido);

        return new OrderResource($pedido->load('items'));
    }

    #[OA\Get(
        path: '/pedidos/{pedido}/historico',
        operationId: 'historicoPedido',
        summary: 'Lista o histórico de status do pedido',
        security: [['bearerAuth' => []]],
        tags: ['Pedidos'],
        parameters: [
            new OA\Parameter(name: 'pedido', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Histórico do pedido.',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/OrderStatusHistory'),
                ),
            ),
            new OA\Response(response: 403, description: 'Acesso negado.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'Pedido não encontrado.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function history(Order $pedido): AnonymousResourceCollection
    {
        $this->authorize('view', $pedido);

        $histories = $pedido->statusHistories()
            ->with('user')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return OrderStatusHistoryResource::collection($histories);
    }

    #[OA\Patch(
        path: '/pedidos/{pedido}/status',
        operationId: 'avancarStatusPedido',
        summary: 'Avança o status do pedido',
        description: 'Somente administradores podem avançar o status, sempre para o próximo da sequência.',
        security: [['bearerAuth' => []]],
        tags: ['Pedidos'],
        parameters: [
            new OA\Parameter(name: 'pedido', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateOrderStatusRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Status atualizado.', content: new OA\JsonContent(ref: '#/components/schemas/Order')),
            new OA\Response(response: 403, description: 'Somente administradores.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'Transição inválida ou conflito de concorrência.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'Status inválido.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function updateStatus(UpdateOrderStatusRequest $request, Order $pedido): OrderResource
    {
        $this->authorize('updateStatus', $pedido);

        $order = $this->orderService->transition(
            $pedido->id,
            OrderStatus::from($request->validated('status')),
            $request->user(),
            $request->validated('reason'),
        );

        return new OrderResource($order);
    }

    #[OA\Post(
        path: '/pedidos/{pedido}/cancelamento',
        operationId: 'cancelarPedido',
        summary: 'Cancela o pedido',
        description: 'O motivo é obrigatório. Clientes cancelam somente pedidos criados ou aguardando pagamento. Pedidos pagos ou posteriores só podem ser cancelados por administradores.',
        security: [['bearerAuth' => []]],
        tags: ['Pedidos'],
        parameters: [
            new OA\Parameter(name: 'pedido', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CancelOrderRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Pedido cancelado.', content: new OA\JsonContent(ref: '#/components/schemas/Order')),
            new OA\Response(response: 403, description: 'Sem permissão para cancelar.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 409, description: 'Pedido não pode ser cancelado.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'Motivo inválido.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function cancel(CancelOrderRequest $request, Order $pedido): OrderResource
    {
        $this->authorize('cancel', $pedido);

        $order = $this->orderService->cancel(
            $pedido->id,
            $request->user(),
            $request->validated('reason'),
        );

        return new OrderResource($order);
    }
}
