<?php

declare(strict_types=1);

namespace App\Tests\ApiPlatform;

use App\Api\Dto\DailyOccurrence;
use App\Tests\ApiPlatform\Trait\GetEntitiesTestTrait;
use App\Tests\ApiPlatform\Trait\GetItemTestTrait;

final class DailyOccurrencesTest extends AbstractApiTestCase
{
    use GetEntitiesTestTrait;
    use GetItemTestTrait;

    protected static string $requestPath = '/api/v2/daily_occurrences';

    protected static string $resourceClass = DailyOccurrence::class;

    // Members are the raw Elasticsearch _source with no @id/@type, so API
    // Platform's self-generated collection schema does not apply (see D6).
    protected static bool $assertsGeneratedCollectionSchema = false;

    protected static int|string $itemId = 10;
}
