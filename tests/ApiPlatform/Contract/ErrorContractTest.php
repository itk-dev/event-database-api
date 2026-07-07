<?php

namespace App\Tests\ApiPlatform\Contract;

use App\Tests\ApiPlatform\AbstractApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Locks the error-response contract. API Platform has changed error
 * serialization across versions (hydra:Error vs problem+json, field names), so
 * the exact shape of 401/404 is worth pinning before the upgrade.
 *
 * Note: the body also carries a `trace` array in the test env (debug on); that
 * is environment-dependent and intentionally NOT asserted here.
 */
class ErrorContractTest extends AbstractApiTestCase
{
    protected static string $requestPath = '/api/v2/events';

    public function testUnauthenticatedErrorShape(): void
    {
        $client = self::createClient();
        $response = $client->request('GET', '/api/v2/events');

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $data = $response->toArray(false);

        $this->assertSame('/api/v2/contexts/Error', $data['@context']);
        $this->assertSame('hydra:Error', $data['@type']);
        $this->assertSame(401, $data['status']);
        $this->assertSame('/errors/401', $data['type']);
        $this->assertArrayHasKey('title', $data);
        $this->assertArrayHasKey('detail', $data);
    }

    public function testNotFoundErrorShape(): void
    {
        $response = $this->get([], '/api/v2/events/99999');

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $data = $response->toArray(false);

        $this->assertSame('/api/v2/contexts/Error', $data['@context']);
        $this->assertSame('hydra:Error', $data['@type']);
        $this->assertSame(404, $data['status']);
        $this->assertSame('/errors/404', $data['type']);
    }
}
