<?php

namespace App\Api\Dto;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Api\Filter\ElasticSearch\MatchFilter;
use App\Api\State\TagRepresentationProvider;

#[ApiResource(
    operations: [
        new Get(
            openapiContext: [
                'summary' => 'Get single tag',
                'parameters' => [
                    [
                        'name' => 'name',
                        'in' => 'path',
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Single tag',
                    ],
                ],
            ],
            output: Tag::class,
            provider: TagRepresentationProvider::class,
        ),
        new GetCollection(
            output: Tag::class,
            provider: TagRepresentationProvider::class,
        ),
    ],
    paginationClientItemsPerPage: true,
    paginationItemsPerPage: 20,
    paginationMaximumItemsPerPage: 100
)]
#[ApiFilter(
    MatchFilter::class,
    properties: ['name']
)]
#[ApiFilter(
    MatchFilter::class,
    properties: ['vocabulary']
)]
readonly class Tag
{
    #[ApiProperty(identifier: false)]
    private ?int $id;

    #[ApiProperty(
        identifier: true,
    )]
    public string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
