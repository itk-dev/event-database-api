<?php

namespace App\Api\Dto;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Api\State\EventRepresentationProvider;

#[ApiResource(operations: [
    new Get(
        openapiContext: [
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
                    'description' => 'Single event',
                ],
            ],
        ],
        output: EventRepresentationProvider::class,
        provider: EventRepresentationProvider::class,
    ),
    new GetCollection(
        output: EventRepresentationProvider::class,
        provider: EventRepresentationProvider::class,
    ),
])]
class Event
{
    #[ApiProperty(
        identifier: true,
    )]
    private int $id;
}
