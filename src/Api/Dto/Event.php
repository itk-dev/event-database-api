<?php

namespace App\Api\Dto;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Api\State\EventRepresentationProvider;

#[ApiResource(operations: [
    new Get(
        openapiContext: [
            'summary' => 'Get single event base on identifier',
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
        ],
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

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }
}
