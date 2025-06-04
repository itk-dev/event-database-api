<?php

namespace App\Tests\ApiPlatform;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use ApiPlatform\Symfony\Bundle\Test\Response;

abstract class AbstractApiTestCase extends ApiTestCase
{
    protected static string $requestPath;

    protected static function createAuthenticatedClient(): Client
    {
        return static::createClient(defaultOptions: [
            'headers' => [
                'accept' => ['application/ld+json'],
                'x-api-key' => 'test_api_key',
            ],
        ]);
    }

    protected function get(array $query, ?string $path = null, bool $authenticated = true): Response
    {
        $path ??= static::$requestPath;

        $client = $authenticated ? self::createAuthenticatedClient() : self::createClient();

        return $client->request('GET', $path.(str_contains($path, '?') ? '&' : '?').http_build_query($query));
    }

    protected static function formatDateTime(string $datetime): string
    {
        return (new \DateTimeImmutable($datetime))->format(\DateTimeImmutable::ATOM);
    }
}
