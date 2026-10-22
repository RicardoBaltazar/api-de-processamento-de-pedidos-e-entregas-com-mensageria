<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Address',
    type: 'object',
    properties: [
        new OA\Property(property: 'postal_code', type: 'string', example: '01310100'),
        new OA\Property(property: 'street', type: 'string', example: 'Avenida Paulista'),
        new OA\Property(property: 'number', type: 'string', example: '1000'),
        new OA\Property(property: 'complement', type: 'string', nullable: true, example: 'Apto 12'),
        new OA\Property(property: 'neighborhood', type: 'string', example: 'Bela Vista'),
        new OA\Property(property: 'city', type: 'string', example: 'São Paulo'),
        new OA\Property(property: 'state', type: 'string', example: 'SP'),
    ],
)]
final class AddressSchema {}
