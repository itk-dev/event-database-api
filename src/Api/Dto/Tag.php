<?php

namespace App\Api\Dto;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Api\Filter\ElasticSearch\MatchFilter;
use App\Api\State\TagRepresentationProvider;

#[ApiResource(
    operations: [
        new Get(
            openapi: new Operation(
                responses: [
                    '200' => new Response(
                        description: 'Get single tag'
                    ),
                ],
                summary: 'Get single tag',
                parameters: [
                    new Parameter(
                        name: 'slug',
                        in: 'path',
                        required: true,
                        schema: [
                            'type' => 'string',
                        ],
                    ),
                ]
            ),
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
    properties: ['name', 'vocabulary']
)]
readonly class Tag
{
    #[ApiProperty(identifier: false)]
    private ?int $id;

    #[ApiProperty(identifier: true)]
    public string $slug;

    public string $name;

    public function __construct(string $name, string $slug)
    {
        $this->name = $name;
        $this->slug = $slug;
    }
}
