<?php

namespace App\Tests\ApiPlatform;

use App\Api\Dto\Event;
use App\Tests\ApiPlatform\Trait\GetEntitiesTestTrait;
use App\Tests\ApiPlatform\Trait\GetItemTestTrait;

/**
 * Test that we can call the API.
 */
class EventsTest extends AbstractApiTestCase
{
    use GetEntitiesTestTrait;
    use GetItemTestTrait;

    protected static string $requestPath = '/api/v2/events';

    protected static string $resourceClass = Event::class;

    protected static int|string $itemId = 7;
}
