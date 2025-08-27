<?php

namespace App\Tests\ApiPlatform;

class ApiTest extends AbstractApiTestCase
{
    public function testRedirectToDocs(): void
    {
        static::createClient()->request('GET', '/');

        $this->assertResponseRedirects('/api/v2/docs');
    }
}
