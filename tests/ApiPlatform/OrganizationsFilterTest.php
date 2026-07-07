<?php

namespace App\Tests\ApiPlatform;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test that filtering organizations work as expected.
 */
class OrganizationsFilterTest extends AbstractApiTestCase
{
    protected static string $requestPath = '/api/v2/organizations';

    #[DataProvider('getOrganizationsProvider')]
    public function testGetOrganizations(array $query, int $expectedCount, ?string $message = null): void
    {
        $message ??= '';

        $response = $this->get($query);

        $data = $response->toArray();
        $this->assertArrayHasKey('hydra:member', $data, $message);
        $this->assertCount($expectedCount, $data['hydra:member'], $message);
    }

    public static function getOrganizationsProvider(): iterable
    {
        // Unfiltered.
        yield [[], 3];

        // MatchFilter on name.
        yield [['name' => 'ITKDev'], 1, 'Organization named "ITKDev"'];
        yield [['name' => 'Dokk1'], 1];
        yield [['name' => 'Aakb'], 1];
        yield [['name' => 'nonexistent'], 0];
    }
}
