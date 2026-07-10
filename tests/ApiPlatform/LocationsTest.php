<?php

declare(strict_types=1);

namespace App\Tests\ApiPlatform;

use App\Api\Dto\Location;
use App\Tests\ApiPlatform\Trait\GetEntitiesTestTrait;
use App\Tests\ApiPlatform\Trait\GetItemTestTrait;

final class LocationsTest extends AbstractApiTestCase
{
    use GetEntitiesTestTrait;
    use GetItemTestTrait;

    protected static string $requestPath = '/api/v2/locations';

    protected static string $resourceClass = Location::class;

    // Members are the raw Elasticsearch _source with no @id/@type, so API
    // Platform's self-generated collection schema does not apply (see D6).
    protected static bool $assertsGeneratedCollectionSchema = false;

    protected static int|string $itemId = 4;
}
