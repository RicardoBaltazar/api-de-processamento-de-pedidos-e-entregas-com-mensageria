<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class ProductController extends Controller
{
    #[OA\Get(
        path: '/produtos',
        operationId: 'listarProdutos',
        summary: 'Lista produtos',
        security: [['bearerAuth' => []]],
        tags: ['Produtos'],
        responses: [
            new OA\Response(response: 200, description: 'Produtos paginados.', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedProducts')),
            new OA\Response(response: 401, description: 'Não autenticado.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Product::class);

        return ProductResource::collection(Product::query()->orderBy('name')->paginate(15));
    }

    #[OA\Post(
        path: '/produtos',
        operationId: 'criarProduto',
        summary: 'Cria um produto',
        security: [['bearerAuth' => []]],
        tags: ['Produtos'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ProductInput')),
        responses: [
            new OA\Response(response: 201, description: 'Produto criado.', content: new OA\JsonContent(ref: '#/components/schemas/Product')),
            new OA\Response(response: 403, description: 'Acesso negado.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'Dados inválidos.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function store(StoreProductRequest $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        $product = Product::create($request->validated());

        return response()->json(new ProductResource($product), 201);
    }

    #[OA\Get(
        path: '/produtos/{produto}',
        operationId: 'exibirProduto',
        summary: 'Exibe um produto',
        security: [['bearerAuth' => []]],
        tags: ['Produtos'],
        parameters: [
            new OA\Parameter(name: 'produto', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Produto encontrado.', content: new OA\JsonContent(ref: '#/components/schemas/Product')),
            new OA\Response(response: 404, description: 'Produto não encontrado.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function show(Product $produto): ProductResource
    {
        $this->authorize('view', $produto);

        return new ProductResource($produto);
    }

    #[OA\Put(
        path: '/produtos/{produto}',
        operationId: 'atualizarProduto',
        summary: 'Atualiza um produto',
        security: [['bearerAuth' => []]],
        tags: ['Produtos'],
        parameters: [
            new OA\Parameter(name: 'produto', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ProductInput')),
        responses: [
            new OA\Response(response: 200, description: 'Produto atualizado.', content: new OA\JsonContent(ref: '#/components/schemas/Product')),
            new OA\Response(response: 403, description: 'Acesso negado.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'Dados inválidos.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    #[OA\Patch(
        path: '/produtos/{produto}',
        operationId: 'atualizarProdutoParcialmente',
        summary: 'Atualiza parcialmente um produto',
        security: [['bearerAuth' => []]],
        tags: ['Produtos'],
        parameters: [
            new OA\Parameter(name: 'produto', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ProductInput')),
        responses: [
            new OA\Response(response: 200, description: 'Produto atualizado.', content: new OA\JsonContent(ref: '#/components/schemas/Product')),
            new OA\Response(response: 403, description: 'Acesso negado.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 422, description: 'Dados inválidos.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function update(UpdateProductRequest $request, Product $produto): ProductResource
    {
        $this->authorize('update', $produto);

        $produto->update($request->validated());

        return new ProductResource($produto);
    }

    #[OA\Delete(
        path: '/produtos/{produto}',
        operationId: 'excluirProduto',
        summary: 'Exclui um produto',
        security: [['bearerAuth' => []]],
        tags: ['Produtos'],
        parameters: [
            new OA\Parameter(name: 'produto', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Produto excluído.'),
            new OA\Response(response: 403, description: 'Acesso negado.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
            new OA\Response(response: 404, description: 'Produto não encontrado.', content: new OA\JsonContent(ref: '#/components/schemas/Error')),
        ],
    )]
    public function destroy(Product $produto): Response
    {
        $this->authorize('delete', $produto);

        $produto->delete();

        return response()->noContent();
    }
}
