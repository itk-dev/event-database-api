<?php

namespace App\Api\Dto;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Api\Filter\StringFieldFilter;
use App\Api\State\OrganizationRepresentationProvider;

#[ApiResource(
    operations: [
        new Get(
            openapiContext: [
                'summary' => 'Get single organization base on identifier',
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
                        'description' => 'Single organization',
                    ],
                ],
            ],
            output: OrganizationRepresentationProvider::class,
            provider: OrganizationRepresentationProvider::class,
        ),
        new GetCollection(
            output: OrganizationRepresentationProvider::class,
            provider: OrganizationRepresentationProvider::class,
        ),
    ],
    paginationClientItemsPerPage: true,
    paginationItemsPerPage: 20,
    paginationMaximumItemsPerPage: 100
)]
// #[ApiFilter(
//    StringFieldFilter::class,
//    properties: [
//        'field' => 'name',
//        'operator' => 'and'
//    ]
// )]
class Organization
{
    #[ApiProperty(
        identifier: true,
    )]
    private int $id;
}
