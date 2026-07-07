<?php

namespace App\Tests\ApiPlatform;

use App\Api\Dto\Organization;
use App\Tests\ApiPlatform\Trait\GetEntitiesTestTrait;
use App\Tests\ApiPlatform\Trait\GetItemTestTrait;

class OrganizationsTest extends AbstractApiTestCase
{
    use GetEntitiesTestTrait;
    use GetItemTestTrait;

    protected static string $requestPath = '/api/v2/organizations';

    protected static string $resourceClass = Organization::class;

    protected static int|string $itemId = 9;
}
