<?php

declare(strict_types=1);

namespace App\Tests\ApiPlatform;

use App\Api\Dto\Tag;
use App\Tests\ApiPlatform\Trait\GetEntitiesTestTrait;
use App\Tests\ApiPlatform\Trait\GetItemTestTrait;

/**
 * Test that we can call the API.
 */
final class TagsTest extends AbstractApiTestCase
{
    use GetEntitiesTestTrait;
    use GetItemTestTrait;

    #[\Override]
    protected static string $requestPath = '/api/v2/tags';

    #[\Override]
    protected static string $resourceClass = Tag::class;

    #[\Override]
    protected static int|string $itemId = 'aros';

    #[\Override]
    protected static int|string $unknownItemId = 'no-such-tag-slug';
}
