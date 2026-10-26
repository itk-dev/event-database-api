<?php

declare(strict_types=1);

namespace App\Tests\ApiPlatform;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verify the API key auth boundary across all resources.
 */
final class AuthenticationTest extends AbstractApiTestCase
{
    protected static string $requestPath = '/api/v2/events';

    #[DataProvider('protectedEndpointProvider')]
    public function testMissingApiKeyReturns401(string $path): void
    {
        self::createClient()
            ->request('GET', $path, ['headers' => ['accept' => 'application/ld+json']]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    #[DataProvider('protectedEndpointProvider')]
    public function testInvalidApiKeyReturns401(string $path): void
    {
        self::createClient()
            ->request('GET', $path, [
                'headers' => [
                    'accept' => 'application/ld+json',
                    'x-api-key' => 'wrong_key',
                ],
            ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    #[DataProvider('protectedEndpointProvider')]
    public function testValidApiKeyReturns200(string $path): void
    {
        $this->get([], $path);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testDocsEndpointIsPublic(): void
    {
        self::createClient()->request('GET', '/api/v2/docs');
        $this->assertResponseIsSuccessful();
    }

    public static function protectedEndpointProvider(): iterable
    {
        yield 'events' => ['/api/v2/events'];
        yield 'occurrences' => ['/api/v2/occurrences'];
        yield 'daily_occurrences' => ['/api/v2/daily_occurrences'];
        yield 'locations' => ['/api/v2/locations'];
        yield 'organizations' => ['/api/v2/organizations'];
        yield 'tags' => ['/api/v2/tags'];
        yield 'vocabularies' => ['/api/v2/vocabularies'];
    }
}
