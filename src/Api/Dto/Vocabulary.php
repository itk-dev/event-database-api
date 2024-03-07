<?php

namespace App\Api\Dto;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Api\Filter\ElasticSearch\MatchFilter;
use App\Api\State\VocabularyRepresentationProvider;

#[ApiResource(
    operations: [
        new Get(
            openapiContext: [
                'summary' => 'Get a vocabulary based on name',
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
                        'description' => 'Single vocabulary',
                    ],
                ],
            ],
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
    properties: ['name']
)]
#[ApiFilter(
    MatchFilter::class,
    properties: ['tags']
)]
readonly class Vocabulary
{
    #[ApiProperty(
        identifier: true,
    )]
    public string $name;

    public string $description;

    public array $tags;

    public function __construct(string $name, string $description, array $tags)
    {
        $this->name = $name;
        $this->description = $description;
        $this->tags = $tags;
    }
}
