<?php

declare(strict_types=1);

namespace App\Tests\ApiPlatform;

use App\Api\Dto\Vocabulary;
use App\Tests\ApiPlatform\Trait\GetEntitiesTestTrait;
use App\Tests\ApiPlatform\Trait\GetItemTestTrait;

/**
 * Test that we can call the API.
 */
final class VocabulariesTest extends AbstractApiTestCase
{
    use GetEntitiesTestTrait;
    use GetItemTestTrait;

    #[\Override]
    protected static string $requestPath = '/api/v2/vocabularies';

    #[\Override]
    protected static string $resourceClass = Vocabulary::class;

    #[\Override]
    protected static int|string $itemId = 'aarhusguiden';

    #[\Override]
    protected static int|string $unknownItemId = 'no-such-vocabulary-slug';
}
