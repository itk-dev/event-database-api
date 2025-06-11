<?php

namespace App\Api\Dto;

use ApiPlatform\Elasticsearch\Filter\MatchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\Response;
use App\Api\Filter\ElasticSearch\BooleanFilter;
use App\Api\Filter\ElasticSearch\DateRangeFilter;
use App\Api\Filter\ElasticSearch\IdFilter;
use App\Api\Filter\ElasticSearch\TagFilter;
use App\Api\State\EventRepresentationProvider;
use App\Model\DateLimit;

#[ApiResource(
    operations: [
        new Get(
            openapi: new Operation(
                responses: [
                    '200' => new Response(
                        description: 'Single event'
                    ),
                ],
                parameters: [
                    new Parameter(
                        name: 'id',
                        in: 'path',
                        required: true,
                        schema: [
                            'type' => 'integer',
                        ],
                    ),
                ]
            ),
            output: EventRepresentationProvider::class,
            provider: EventRepresentationProvider::class,
        ),
        new GetCollection(
            output: EventRepresentationProvider::class,
            provider: EventRepresentationProvider::class,
        ),
    ],
    paginationClientItemsPerPage: true,
    paginationItemsPerPage: 10,
    paginationMaximumItemsPerPage: 50
)]
#[ApiFilter(
    MatchFilter::class,
    properties: ['title', 'organizer.name', 'location.name']
)]
#[ApiFilter(
    IdFilter::class,
    properties: ['organizer.entityId', 'location.entityId']
)]
#[ApiFilter(
    BooleanFilter::class,
    properties: ['publicAccess']
)]
#[ApiFilter(
    TagFilter::class,
    properties: ['tags']
)]
#[ApiFilter(
    DateRangeFilter::class,
    properties: [
        'occurrences.start' => 'gte',
        'occurrences.end' => 'lte',
        'updated' => 'gte',
    ],
    // Arguments only exist to provide backward compatibility with filters originally defined by the Date filter
    arguments: [
        'config' => [
            'gte' => [
                'limit' => DateLimit::gte,
                'throwOnInvalid' => true,
            ],
            'lte' => [
                'limit' => DateLimit::lte,
                'throwOnInvalid' => true,
            ],
        ],
    ]
)]
class Event
{
    #[ApiProperty(
        identifier: true,
    )]
    private int $id;
}
