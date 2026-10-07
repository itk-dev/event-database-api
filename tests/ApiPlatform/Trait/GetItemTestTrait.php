<?php

namespace App\Tests\ApiPlatform\Trait;

use App\Tests\ApiPlatform\AbstractApiTestCase;
use Symfony\Component\HttpFoundation\Response;

trait GetItemTestTrait
{
    public function testGetItem(): void
    {
        assert($this instanceof AbstractApiTestCase);

        $path = static::$requestPath.'/'.static::$itemId;

        $this->get([], $path, authenticated: false);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        $response = $this->get([], $path);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJson($response->getContent());
        $this->assertMatchesResourceItemJsonSchema(static::$resourceClass);
    }

    public function testGetItemNotFound(): void
    {
        assert($this instanceof AbstractApiTestCase);

        $path = static::$requestPath.'/'.static::$unknownItemId;

        $this->get([], $path);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
