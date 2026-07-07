<?php

namespace App\Tests\ApiPlatform;

use App\Api\Dto\Tag;
use App\Tests\ApiPlatform\Trait\GetEntitiesTestTrait;
use App\Tests\ApiPlatform\Trait\GetItemTestTrait;

/**
 * Test that we can call the API.
 */
class TagsTest extends AbstractApiTestCase
{
    use GetEntitiesTestTrait;
    use GetItemTestTrait;

    protected static string $requestPath = '/api/v2/tags';

    protected static string $resourceClass = Tag::class;

    protected static int|string $itemId = 'aros';

    protected static int|string $unknownItemId = 'no-such-tag-slug';
}
