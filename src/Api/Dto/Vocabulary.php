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
use App\Api\State\VocabularyRepresentationProvider;

#[ApiResource(
    operations: [
        new Get(
            openapi: new Operation(
                responses: [
                    '200' => new Response(
                        description: 'Get single vocabulary'
                    ),
                ],
                summary: 'Get a vocabulary based on slug',
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
            output: Vocabulary::class,
            provider: VocabularyRepresentationProvider::class,
        ),
        new GetCollection(
            output: Vocabulary::class,
            provider: VocabularyRepresentationProvider::class,
        ),
    ],
    paginationClientItemsPerPage: true,
    paginationItemsPerPage: 20,
    paginationMaximumItemsPerPage: 100
)]
#[ApiFilter(
    MatchFilter::class,
    properties: ['name', 'tags']
)]
readonly class Vocabulary
{
    #[ApiProperty(
        identifier: true,
    )]
    public string $slug;

    public string $name;

    public string $description;

    public array $tags;

    public function __construct(string $name, string $slug, string $description, array $tags)
    {
        $this->name = $name;
        $this->slug = $slug;
        $this->description = $description;
        $this->tags = $tags;
    }
}
