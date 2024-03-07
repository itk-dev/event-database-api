<?php

namespace App\Api\Dto;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Api\Filter\ElasticSearch\MatchFilter;
use App\Api\State\LocationRepresentationProvider;

#[ApiResource(
    operations: [
        new Get(
            openapiContext: [
                'summary' => 'Get single location based on identifier',
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
                        'description' => 'Single location',
                    ],
                ],
            ],
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
class Location
{
    #[ApiProperty(
        identifier: true,
    )]
    private int $id;
}
