<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProductInput',
    type: 'object',
    required: ['name', 'price'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Camiseta'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'price', type: 'number', format: 'float', example: 59.9),
        new OA\Property(property: 'active', type: 'boolean', example: true),
    ],
)]
final class ProductInputSchema {}
