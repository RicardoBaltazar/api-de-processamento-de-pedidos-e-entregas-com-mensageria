<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'OrderStatusHistory',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'from_status', type: 'string', nullable: true, example: 'CRIADO'),
        new OA\Property(property: 'to_status', type: 'string', example: 'AGUARDANDO_PAGAMENTO'),
        new OA\Property(property: 'reason', type: 'string', nullable: true),
        new OA\Property(
            property: 'actor',
            type: 'object',
            nullable: true,
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'name', type: 'string', example: 'Administrador'),
            ],
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
final class OrderStatusHistorySchema {}
