<?php

namespace App\Api\Dto;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Api\Filter\ElasticSearch\DateFilter;
use App\Api\Filter\ElasticSearch\EventTagFilter;
use App\Api\State\EventRepresentationProvider;
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
                        'description' => 'Single event',
                    ],
                ],
            ],
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
    EventTagFilter::class,
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
