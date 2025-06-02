<?php

namespace App\Tests\ApiPlatform;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;

abstract class AbstractApiTestCase extends ApiTestCase
{
    protected static function createAuthenticatedClient(): Client
    {
        return static::createClient(defaultOptions: [
            'headers' => [
                'accept' => ['application/ld+json'],
                'x-api-key' => 'test_api_key',
            ],
        ]);
    }
}
