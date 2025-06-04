<?php

namespace App\Tests\ApiPlatform;

use App\Tests\ApiPlatform\Trait\GetEntitiesTestTrait;

/**
 * Test that we can call the API.
 */
class VocabulariesTest extends AbstractApiTestCase
{
    use GetEntitiesTestTrait;

    protected static string $requestPath = '/api/v2/vocabularies';
}
