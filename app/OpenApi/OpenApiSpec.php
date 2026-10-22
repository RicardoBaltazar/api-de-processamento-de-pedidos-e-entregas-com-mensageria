<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'API de Processamento de Pedidos e Entregas',
    description: 'API para autenticação, catálogo de produtos e gestão de pedidos com histórico de status.',
)]
#[OA\Server(url: '/api', description: 'Servidor da API')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum',
    description: 'Informe o token retornado no login ou registro.',
)]
#[OA\Tag(name: 'Autenticação', description: 'Registro, login e dados do usuário autenticado.')]
#[OA\Tag(name: 'Produtos', description: 'Catálogo de produtos. Criação, edição e exclusão são restritas a administradores.')]
#[OA\Tag(name: 'Pedidos', description: 'Criação, consulta, histórico, avanço de status e cancelamento de pedidos.')]
final class OpenApiSpec {}
