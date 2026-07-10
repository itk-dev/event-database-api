<?php

declare(strict_types=1);

namespace App\Tests\ApiPlatform\Contract;

use App\Tests\ApiPlatform\AbstractApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Locks the error-response contract. API Platform has changed error
 * serialization across versions (hydra:Error vs problem+json, field names), so
 * the exact shape of 401/404 is worth pinning.
 *
 * Under API Platform 4.3 (with `hydra_prefix: true`) the human-readable fields
 * are hydra-prefixed — `hydra:title` / `hydra:description` — while the
 * machine-readable `status`, `type` and `detail` are unprefixed. (In 4.1 the
 * title was emitted unprefixed as `title`.)
 *
 * Note: the body also carries a `trace` array in the test env (debug on); that
 * is environment-dependent and intentionally NOT asserted here.
 */
final class ErrorContractTest extends AbstractApiTestCase
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
        $this->assertArrayHasKey('detail', $data);
        // 4.3 hydra-prefixes the human-readable fields (was unprefixed `title` in 4.1).
        $this->assertArrayHasKey('hydra:title', $data);
        $this->assertArrayHasKey('hydra:description', $data);
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

    /**
     * `rfc_7807_compliant_errors: true` (config/packages/api_platform.yaml)
     * registers problem+json as an error format, so an auth error requested as
     * `application/problem+json` is served as RFC 7807 (unprefixed
     * title/detail/status/type) rather than hydra. The 401 also short-circuits
     * before resource content negotiation, so the format is honoured.
     *
     * @see https://api-platform.com/docs/core/content-negotiation/
     */
    public function testUnauthenticatedErrorIsAvailableAsProblemJson(): void
    {
        $client = self::createClient(defaultOptions: [
            'headers' => ['accept' => ['application/problem+json']],
        ]);
        $response = $client->request('GET', '/api/v2/events');

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertStringContainsString('application/problem+json', $response->getHeaders(false)['content-type'][0] ?? '');

        $data = $response->toArray(false);
        $this->assertSame(401, $data['status']);
        $this->assertSame('/errors/401', $data['type']);
        $this->assertArrayHasKey('title', $data);
        $this->assertArrayHasKey('detail', $data);
    }

    /**
     * The only resource format configured under `formats` in
     * config/packages/api_platform.yaml is `application/ld+json`, so content
     * negotiation cannot satisfy `Accept: application/problem+json` on a resource
     * read: it yields 406 Not Acceptable (NOT a 404), and the 406 body is itself
     * a problem+json error. Pinning this documents that problem+json is not a
     * resource representation (consumers must read resources as ld+json) and
     * guards the `formats` allow-list — adding problem+json there would flip this.
     *
     * @see https://api-platform.com/docs/core/content-negotiation/
     */
    public function testProblemJsonIsNotAcceptableForResourceReads(): void
    {
        $client = self::createClient(defaultOptions: [
            'headers' => [
                'accept' => ['application/problem+json'],
                'x-api-key' => 'test_api_key',
            ],
        ]);
        $response = $client->request('GET', '/api/v2/events/99999');

        $this->assertSame(Response::HTTP_NOT_ACCEPTABLE, $response->getStatusCode());
        $this->assertStringContainsString('application/problem+json', $response->getHeaders(false)['content-type'][0] ?? '');

        $data = $response->toArray(false);
        $this->assertSame(406, $data['status']);
    }
}
