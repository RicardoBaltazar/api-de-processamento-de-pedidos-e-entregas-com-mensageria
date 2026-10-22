<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UpdateOrderStatusRequest',
    type: 'object',
    required: ['status'],
    properties: [
        new OA\Property(
            property: 'status',
            type: 'string',
            enum: ['CRIADO', 'AGUARDANDO_PAGAMENTO', 'PAGO', 'EM_PREPARACAO', 'ENVIADO', 'ENTREGUE'],
            example: 'AGUARDANDO_PAGAMENTO',
        ),
        new OA\Property(property: 'reason', type: 'string', nullable: true, maxLength: 1000),
    ],
)]
final class UpdateOrderStatusRequestSchema {}
