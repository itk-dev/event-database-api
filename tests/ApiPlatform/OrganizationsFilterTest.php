<?php

declare(strict_types=1);

namespace App\Tests\ApiPlatform;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test that filtering organizations works as expected.
 *
 * Assertions pin the exact set of matching `entityId`s. Fixture ids: 9 (ITKDev),
 * 10 (Aakb), 11 (Dokk1). See tests/resources/organizations.json.
 */
final class OrganizationsFilterTest extends AbstractApiTestCase
{
    protected static string $requestPath = '/api/v2/organizations';

    #[DataProvider('getOrganizationsProvider')]
    public function testGetOrganizations(array $query, array $expectedIds, ?string $message = null): void
    {
        $response = $this->get($query);

        $this->assertMemberIds($expectedIds, $response, 'entityId', message: $message ?? '');
    }

    public static function getOrganizationsProvider(): iterable
    {
        yield 'unfiltered' => [[], [9, 10, 11]];

        // MatchFilter on name (`name` is a `text` field → token match).
        yield 'name ITKDev' => [['name' => 'ITKDev'], [9], 'Organization 9 is "ITKDev"'];
        yield 'name Dokk1' => [['name' => 'Dokk1'], [11]];
        yield 'name Aakb' => [['name' => 'Aakb'], [10]];
        yield 'name nonexistent' => [['name' => 'nonexistent'], []];
    }
}
