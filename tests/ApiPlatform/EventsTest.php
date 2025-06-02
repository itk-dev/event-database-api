<?php

namespace App\Tests\ApiPlatform;

use Symfony\Component\HttpFoundation\Response;

class EventsTest extends AbstractApiTestCase
{
    public function testGetEvents(): void
    {
        static::createClient()
            ->request('GET', '/api/v2/events');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        $response = static::createAuthenticatedClient()
            ->request('GET', '/api/v2/events');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJson($response->getContent());

        $data = $response->toArray();
        $this->assertArrayHasKey('hydra:member', $data);
        $this->assertCount(2, $data['hydra:member']);
    }
}
