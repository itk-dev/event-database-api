<?php

namespace App\Api\Dto;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Api\State\OrganizationRepresentationProvider;

#[ApiResource(operations: [
    new Get(
        openapiContext: [
            'summary' => 'Get single organization base on identifier',
            'parameters' => [
                [
                    'name' => 'id',
                    'in' => 'path',
                    'required' => true,
                    'schema' => [
                        'type' => 'integer',
                    ],
                ],
            ],
            'responses' => [
                '200' => [
                    'description' => 'Single organization',
                ],
            ],
        ],
        output: OrganizationRepresentationProvider::class,
        provider: OrganizationRepresentationProvider::class,
    ),
])]
class Organization
{
    #[ApiProperty(
        identifier: true,
    )]
    private int $id;
}
