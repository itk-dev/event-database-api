<?php

namespace App\Tests\ApiPlatform;

use App\Api\Dto\Location;
use App\Tests\ApiPlatform\Trait\GetEntitiesTestTrait;
use App\Tests\ApiPlatform\Trait\GetItemTestTrait;

class LocationsTest extends AbstractApiTestCase
{
    use GetEntitiesTestTrait;
    use GetItemTestTrait;

    protected static string $requestPath = '/api/v2/locations';

    protected static string $resourceClass = Location::class;

    protected static int|string $itemId = 4;
}
