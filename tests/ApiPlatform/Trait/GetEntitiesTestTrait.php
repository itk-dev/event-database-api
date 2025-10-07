<?php

namespace App\Tests\ApiPlatform\Trait;

use App\Tests\ApiPlatform\AbstractApiTestCase;
use Symfony\Component\HttpFoundation\Response;

trait GetEntitiesTestTrait
{
    public function testGetEntities(): void
    {
        assert($this instanceof AbstractApiTestCase);
        $this->get([], authenticated: false);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        $response = $this->get([]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJson($response->getContent());
    }
}
