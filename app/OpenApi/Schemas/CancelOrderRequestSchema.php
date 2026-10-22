<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CancelOrderRequest',
    type: 'object',
    required: ['reason'],
    properties: [
        new OA\Property(property: 'reason', type: 'string', maxLength: 1000, example: 'Cliente solicitou o cancelamento.'),
    ],
)]
final class CancelOrderRequestSchema {}
