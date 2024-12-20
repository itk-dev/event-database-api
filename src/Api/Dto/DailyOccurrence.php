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
use App\Api\Filter\ElasticSearch\IdFilter;
use App\Api\Filter\ElasticSearch\MatchFilter;
use App\Api\Filter\ElasticSearch\TagFilter;
use App\Api\State\DailyOccurrenceRepresentationProvider;
use App\Model\DateLimits;

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
    DateFilter::class,
    properties: [
        'start' => 'start',
        'end' => 'end',
        'updated' => 'start',
    ],
    arguments: [
        'config' => [
            'start' => [
                'limit' => DateLimits::GreaterThanOrEqual,
                'throwOnInvalid' => true,
            ],
            'end' => [
                'limit' => DateLimits::LessThanOrEqual,
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
