<?php

declare(strict_types=1);

namespace App\Tests\ApiPlatform;

use App\Api\Dto\Organization;
use App\Tests\ApiPlatform\Trait\GetEntitiesTestTrait;
use App\Tests\ApiPlatform\Trait\GetItemTestTrait;

final class OrganizationsTest extends AbstractApiTestCase
{
    use GetEntitiesTestTrait;
    use GetItemTestTrait;

    #[\Override]
    protected static string $requestPath = '/api/v2/organizations';

    #[\Override]
    protected static string $resourceClass = Organization::class;

    // Members are the raw Elasticsearch _source with no @id/@type, so API
    // Platform's self-generated collection schema does not apply (see D6).
    #[\Override]
    protected static bool $assertsGeneratedCollectionSchema = false;

    #[\Override]
    protected static int|string $itemId = 9;
}
