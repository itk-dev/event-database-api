<?php

declare(strict_types=1);

namespace App\Tests\ApiPlatform\Contract;

use App\Tests\ApiPlatform\AbstractApiTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Locks content negotiation. The API currently serves ONLY application/ld+json:
 * requesting application/json or text/html yields 406 Not Acceptable. This is a
 * strong client-facing contract and a likely BC-sensitive area on upgrade
 * (default formats, negotiation behaviour).
 */
final class ContentNegotiationContractTest extends AbstractApiTestCase
{
    protected static string $requestPath = '/api/v2/events';

    public function testLdJsonIsSupported(): void
    {
        $response = $this->requestWithAccept('application/ld+json');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
    }

    public function testPlainJsonIsNotAcceptable(): void
    {
        $response = $this->requestWithAccept('application/json');

        $this->assertSame(Response::HTTP_NOT_ACCEPTABLE, $response->getStatusCode(), 'The API currently rejects application/json');
    }

    public function testHtmlIsNotAcceptable(): void
    {
        $response = $this->requestWithAccept('text/html');

        $this->assertSame(Response::HTTP_NOT_ACCEPTABLE, $response->getStatusCode(), 'The API currently rejects text/html on resource endpoints');
    }

    private function requestWithAccept(string $accept): ResponseInterface
    {
        $client = self::createClient(defaultOptions: [
            'headers' => [
                'accept' => [$accept],
                'x-api-key' => 'test_api_key',
            ],
        ]);

        return $client->request('GET', '/api/v2/events');
    }
}
