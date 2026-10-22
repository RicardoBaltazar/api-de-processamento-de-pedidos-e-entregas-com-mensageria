<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StoreOrderRequest',
    type: 'object',
    required: ['items', 'postal_code', 'street', 'number', 'neighborhood', 'city', 'state'],
    properties: [
        new OA\Property(property: 'customer_id', type: 'integer', nullable: true, description: 'Somente admin pode informar outro cliente.'),
        new OA\Property(
            property: 'items',
            type: 'array',
            minItems: 1,
            items: new OA\Items(ref: '#/components/schemas/OrderItemInput'),
        ),
        new OA\Property(property: 'postal_code', type: 'string', example: '01310100'),
        new OA\Property(property: 'street', type: 'string', example: 'Avenida Paulista'),
        new OA\Property(property: 'number', type: 'string', example: '1000'),
        new OA\Property(property: 'complement', type: 'string', nullable: true),
        new OA\Property(property: 'neighborhood', type: 'string', example: 'Bela Vista'),
        new OA\Property(property: 'city', type: 'string', example: 'São Paulo'),
        new OA\Property(property: 'state', type: 'string', maxLength: 2, example: 'SP'),
    ],
)]
final class StoreOrderRequestSchema {}
