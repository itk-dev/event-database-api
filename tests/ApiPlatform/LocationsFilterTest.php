<?php

namespace App\Tests\ApiPlatform;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test that filtering locations work as expected.
 */
class LocationsFilterTest extends AbstractApiTestCase
{
    protected static string $requestPath = '/api/v2/locations';

    #[DataProvider('getLocationsProvider')]
    public function testGetLocations(array $query, int $expectedCount, ?string $message = null): void
    {
        $message ??= '';

        $response = $this->get($query);

        $data = $response->toArray();
        $this->assertArrayHasKey('hydra:member', $data, $message);
        $this->assertCount($expectedCount, $data['hydra:member'], $message);
    }

    public static function getLocationsProvider(): iterable
    {
        // Unfiltered.
        yield [[], 2];

        // MatchFilter on name.
        yield [['name' => 'ITK Development'], 1, 'Location named "ITK Development"'];
        yield [['name' => 'Somewhere'], 1, 'Location named "Somewhere"'];
        yield [['name' => 'nonexistent'], 0];

        // MatchFilter on postalCode.
        yield [['postalCode' => '8000'], 1, 'Location with postal code 8000'];
        yield [['postalCode' => '9999'], 0];
    }
}
