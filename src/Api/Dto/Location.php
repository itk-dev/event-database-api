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
use App\Api\Filter\ElasticSearch\DateRangeFilter;
use App\Api\Filter\ElasticSearch\MatchFilter;
use App\Api\State\LocationRepresentationProvider;
use App\Model\DateLimit;

#[ApiResource(
    operations: [
        new Get(
            openapi: new Operation(
                responses: [
                    '200' => new Response(
                        description: 'Single location'
                    ),
                ],
                summary: 'Get single location based on identifier',
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
            output: LocationRepresentationProvider::class,
            provider: LocationRepresentationProvider::class,
        ),
        new GetCollection(
            output: LocationRepresentationProvider::class,
            provider: LocationRepresentationProvider::class,
        ),
    ],
    paginationClientItemsPerPage: true,
    paginationItemsPerPage: 20,
    paginationMaximumItemsPerPage: 100
)]
#[ApiFilter(
    MatchFilter::class,
    properties: ['name', 'postalCode']
)]
#[ApiFilter(
    DateRangeFilter::class,
    properties: [
        'updated' => 'start',
    ],
    // Arguments only exist to provide backward compatibility with filters originally defined by the Date filter
    arguments: [
        'config' => [
            'start' => [
                'limit' => DateLimit::gte,
                'throwOnInvalid' => true,
            ],
        ],
    ]
)]
class Location
{
    #[ApiProperty(
        identifier: true,
    )]
    private int $id;
}
