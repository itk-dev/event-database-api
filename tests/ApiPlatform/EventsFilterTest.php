<?php

namespace App\Tests\ApiPlatform;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test that filtering events work as expected.
 */
class EventsFilterTest extends AbstractApiTestCase
{
    protected static string $requestPath = '/api/v2/events';

    #[DataProvider('getEventsProvider')]
    public function testGetEvents(array $query, int $expectedCount, ?string $message = null): void
    {
        $message ??= '';

        $response = $this->get($query);

        $data = $response->toArray();
        $this->assertArrayHasKey('hydra:member', $data, $message);
        $this->assertCount($expectedCount, $data['hydra:member'], $message);
    }

    public static function getEventsProvider(): iterable
    {
        yield [
            [],
            3,
        ];

        // Test BooleanFilter.
        yield [
            ['publicAccess' => 'true'],
            2,
        ];

        yield [
            ['publicAccess' => 'false'],
            1,
        ];

        // Test DateRangeFilter on occurrences.start.
        yield [
            ['occurrences.start[between]' => static::formatDateTime('2001-01-01').'..'.static::formatDateTime('2100-01-01')],
            3,
            'Events in 21st century',
        ];

        yield [
            ['occurrences.start[between]' => static::formatDateTime('2026-01-01').'..'.static::formatDateTime('2026-12-31')],
            1,
            'Events in 2026',
        ];

        // Test DateRangeFilter on updated (default operator: gte).
        yield [
            ['updated' => static::formatDateTime('2024-01-01')],
            3,
            'Events updated on or after 2024-01-01',
        ];

        yield [
            ['updated[gte]' => static::formatDateTime('2100-01-01')],
            0,
            'No events updated after 2100',
        ];

        // Test IdFilter.
        yield [
            ['organizer.entityId' => 9],
            2,
        ];

        yield [
            ['organizer.entityId' => 11],
            1,
        ];

        // Test MatchFilter.
        yield [
            ['location.name' => 'somewhere'],
            1,
            'An event somewhere',
        ];

        yield [
            ['location.name' => 'Another place'],
            0,
        ];

        // Test TagFilter. In production `tags` is a keyword field (see event-database-imports
        // Mappings/Event) — matching is exact, case-sensitive and whole-value (no tokenisation).
        yield [
            ['tags' => 'ITKDev'],
            2,
            'Events tagged with "ITKDev" (exact keyword match)',
        ];

        yield [
            ['tags' => 'itkdev'],
            0,
            'Tag matching is case-sensitive: "itkdev" does not match "ITKDev"',
        ];

        yield [
            ['tags' => 'aros'],
            3,
            'All fixture events are tagged "aros"',
        ];

        yield [
            ['tags' => 'for-boern'],
            1,
            'A hyphenated tag matches as a whole value, not by token',
        ];

        yield [
            ['tags' => 'boern'],
            0,
            'No substring/token match on a keyword field',
        ];

        yield [
            ['tags' => 'itkdevelopment'],
            0,
            'Events tagged with "itkdevelopment"',
        ];

        // Combined filters.
        yield [
            [
                'occurrences.start[between]' => static::formatDateTime('2026-01-01').'..'.static::formatDateTime('2026-12-31'),
                'tags' => 'aros',
            ],
            1,
            'Events in 2026 also tagged "aros"',
        ];
    }
}
