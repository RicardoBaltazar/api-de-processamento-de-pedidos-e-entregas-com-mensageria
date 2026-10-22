<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Error',
    type: 'object',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Mensagem de erro.'),
        new OA\Property(property: 'errors', type: 'object', nullable: true),
    ],
)]
final class ErrorSchema {}
