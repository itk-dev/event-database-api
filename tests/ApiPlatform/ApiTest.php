<?php

declare(strict_types=1);

namespace App\Tests\ApiPlatform;

final class ApiTest extends AbstractApiTestCase
{
    public function testRedirectToDocs(): void
    {
        self::createClient()->request('GET', '/');

        $this->assertResponseRedirects('/api/v2/docs');
    }
}
