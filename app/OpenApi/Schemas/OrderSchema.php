<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Order',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'customer_id', type: 'integer', example: 2),
        new OA\Property(
            property: 'status',
            type: 'string',
            enum: ['CRIADO', 'AGUARDANDO_PAGAMENTO', 'PAGO', 'EM_PREPARACAO', 'ENVIADO', 'ENTREGUE', 'CANCELADO'],
            example: 'CRIADO',
        ),
        new OA\Property(property: 'total', type: 'string', example: '119.80'),
        new OA\Property(property: 'address', ref: '#/components/schemas/Address'),
        new OA\Property(
            property: 'items',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/OrderItem'),
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
final class OrderSchema {}
