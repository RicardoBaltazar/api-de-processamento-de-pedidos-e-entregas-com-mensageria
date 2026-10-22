<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'OrderItem',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'product_id', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'product_name', type: 'string', example: 'Camiseta'),
        new OA\Property(property: 'unit_price', type: 'string', example: '59.90'),
        new OA\Property(property: 'quantity', type: 'integer', example: 2),
        new OA\Property(property: 'subtotal', type: 'string', example: '119.80'),
    ],
)]
final class OrderItemSchema {}
