<?php

namespace App\Tests\ApiPlatform;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class AbstractApiTestCase extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = false;

    protected static string $requestPath;

    protected static string $resourceClass;

    protected static int|string $itemId;

    protected static int|string $unknownItemId = 99999;

    protected static function createAuthenticatedClient(): Client
    {
        return static::createClient(defaultOptions: [
            'headers' => [
                'accept' => ['application/ld+json'],
                'x-api-key' => 'test_api_key',
            ],
        ]);
    }

    protected function get(array $query, ?string $path = null, bool $authenticated = true): ResponseInterface
    {
        $path ??= static::$requestPath;

        $client = $authenticated ? self::createAuthenticatedClient() : self::createClient();

        return $client->request('GET', $path.(str_contains($path, '?') ? '&' : '?').http_build_query($query));
    }

    protected static function formatDateTime(string $datetime): string
    {
        return (new \DateTimeImmutable($datetime))->format(\DateTimeImmutable::ATOM);
    }

    /**
     * Assert a collection response contains exactly the expected member identities.
     *
     * Reads $field from each `hydra:member` — `entityId` for most resources,
     * `slug` for Tag/Vocabulary. The comparison is order-independent by default;
     * pass $ordered to also assert the sequence (used by the sort contract).
     *
     * @param array<int|string>                                $expected
     */
    protected function assertMemberIds(array $expected, ResponseInterface $response, string $field = 'entityId', bool $ordered = false, string $message = ''): void
    {
        $data = $response->toArray();
        self::assertArrayHasKey('hydra:member', $data, $message);

        $actual = array_map(
            static fn (array $member) => $member[$field] ?? null,
            $data['hydra:member']
        );

        if ($ordered) {
            self::assertSame($expected, $actual, $message);

            return;
        }

        sort($expected);
        sort($actual);
        self::assertSame($expected, $actual, $message);
    }
}
