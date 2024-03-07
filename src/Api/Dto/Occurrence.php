<?php

namespace App\Api\Dto;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Api\Filter\ElasticSearch\BooleanFilter;
use App\Api\Filter\ElasticSearch\DateFilter;
use App\Api\Filter\ElasticSearch\EventTagFilter;
use App\Api\Filter\ElasticSearch\MatchFilter;
use App\Api\State\OccurrenceRepresentationProvider;
use App\Model\DateLimits;

#[ApiResource(
    operations: [
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
                        'description' => 'Single occurrence',
                    ],
                ],
            ],
            output: OccurrenceRepresentationProvider::class,
            provider: OccurrenceRepresentationProvider::class,
        ),
        new GetCollection(
            output: OccurrenceRepresentationProvider::class,
            provider: OccurrenceRepresentationProvider::class,
        ),
    ],
    paginationClientItemsPerPage: true,
    paginationItemsPerPage: 10,
    paginationMaximumItemsPerPage: 50
)]
#[ApiFilter(
    MatchFilter::class,
    properties: ['event.title', 'event.organizer.name', 'event.organizer.entityId', 'event.location.name', 'event.location.entityId']
)]
#[ApiFilter(
    BooleanFilter::class,
    properties: ['event.publicAccess']
)]
#[ApiFilter(
    EventTagFilter::class,
    properties: ['event.tags']
)]
#[ApiFilter(
    DateFilter::class,
    properties: [
        'start' => 'start',
        'end' => 'end',
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
class Occurrence
{
    #[ApiProperty(
        identifier: true,
    )]
    private int $id;
}
