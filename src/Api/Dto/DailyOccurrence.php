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
use App\Api\Filter\ElasticSearch\BooleanFilter;
use App\Api\Filter\ElasticSearch\DateFilter;
use App\Api\Filter\ElasticSearch\DateRangeFilter;
use App\Api\Filter\ElasticSearch\IdFilter;
use App\Api\Filter\ElasticSearch\MatchFilter;
use App\Api\Filter\ElasticSearch\TagFilter;
use App\Api\State\DailyOccurrenceRepresentationProvider;
use App\Model\DateLimit;

#[ApiResource(
    operations: [
        new Get(
            openapi: new Operation(
                responses: [
                    '200' => new Response(
                        description: 'Single daily occurrence'
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
                ],
            ),
            output: DailyOccurrenceRepresentationProvider::class,
            provider: DailyOccurrenceRepresentationProvider::class,
        ),
        new GetCollection(
            output: DailyOccurrenceRepresentationProvider::class,
            provider: DailyOccurrenceRepresentationProvider::class,
        ),
    ],
    paginationClientItemsPerPage: true,
    paginationItemsPerPage: 10,
    paginationMaximumItemsPerPage: 50
)]
#[ApiFilter(
    MatchFilter::class,
    properties: ['event.title', 'event.organizer.name', 'event.location.name']
)]
#[ApiFilter(
    IdFilter::class,
    properties: ['event.organizer.entityId', 'event.location.entityId']
)]
#[ApiFilter(
    BooleanFilter::class,
    properties: ['event.publicAccess']
)]
#[ApiFilter(
    TagFilter::class,
    properties: ['event.tags']
)]
#[ApiFilter(
    DateRangeFilter::class,
    properties: [
        'start' => 'gte',
        'end' => 'lte',
        'updated' => 'gte',
    ],
    // Arguments only exist to provide backward compatibility with filters originally defined by the DateFilter
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
class DailyOccurrence
{
    #[ApiProperty(
        identifier: true,
    )]
    private int $id;
}
