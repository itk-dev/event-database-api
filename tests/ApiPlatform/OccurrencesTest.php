<?php

namespace App\Tests\ApiPlatform;

use App\Api\Dto\Occurrence;
use App\Tests\ApiPlatform\Trait\GetEntitiesTestTrait;
use App\Tests\ApiPlatform\Trait\GetItemTestTrait;

class OccurrencesTest extends AbstractApiTestCase
{
    use GetEntitiesTestTrait;
    use GetItemTestTrait;

    protected static string $requestPath = '/api/v2/occurrences';

    protected static string $resourceClass = Occurrence::class;

    // Members are the raw Elasticsearch _source with no @id/@type, so API
    // Platform's self-generated collection schema does not apply (see D6).
    protected static bool $assertsGeneratedCollectionSchema = false;

    protected static int|string $itemId = 10;
}
