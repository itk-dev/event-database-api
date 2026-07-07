<?php

namespace App\Tests\ApiPlatform;

use App\Api\Dto\DailyOccurrence;
use App\Tests\ApiPlatform\Trait\GetEntitiesTestTrait;
use App\Tests\ApiPlatform\Trait\GetItemTestTrait;

class DailyOccurrencesTest extends AbstractApiTestCase
{
    use GetEntitiesTestTrait;
    use GetItemTestTrait;

    protected static string $requestPath = '/api/v2/daily_occurrences';

    protected static string $resourceClass = DailyOccurrence::class;

    protected static int|string $itemId = 10;
}
