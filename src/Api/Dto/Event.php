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
use App\Api\State\EventRepresentationProvider;
use App\Model\DateLimits;

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
    DateFilter::class,
    properties: [
        'occurrences.start' => 'start',
        'occurrences.end' => 'end',
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
class Event
{
    #[ApiProperty(
        identifier: true,
    )]
    private int $id;
}
