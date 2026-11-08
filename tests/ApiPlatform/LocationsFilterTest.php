<?php

declare(strict_types=1);

namespace App\Tests\ApiPlatform;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test that filtering locations works as expected.
 *
 * Assertions pin the exact set of matching `entityId`s. Fixture ids: 4 (ITK
 * Development, postal 8000), 5 (Somewhere). See tests/resources/locations.json.
 */
final class LocationsFilterTest extends AbstractApiTestCase
{
    protected static string $requestPath = '/api/v2/locations';

    #[DataProvider('getLocationsProvider')]
    public function testGetLocations(array $query, array $expectedIds, ?string $message = null): void
    {
        $response = $this->get($query);

        $this->assertMemberIds($expectedIds, $response, 'entityId', message: $message ?? '');
    }

    public static function getLocationsProvider(): iterable
    {
        yield 'unfiltered' => [[], [4, 5]];

        // MatchFilter on name (`name` is a `text` field → token match).
        yield 'name ITK Development' => [['name' => 'ITK Development'], [4], 'Location 4 is "ITK Development"'];
        yield 'name Somewhere' => [['name' => 'Somewhere'], [5], 'Location 5 is "Somewhere"'];
        yield 'name nonexistent' => [['name' => 'nonexistent'], []];

        // MatchFilter on postalCode (`postalCode` is a `keyword` field → exact match).
        yield 'postalCode 8000' => [['postalCode' => '8000'], [4], 'Location 4 has postal code 8000'];
        yield 'postalCode 9999' => [['postalCode' => '9999'], []];
    }
}
