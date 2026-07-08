<?php

namespace App\Tests\ApiPlatform;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test that filtering events works as expected.
 *
 * Assertions pin the exact set of matching event `entityId`s (not just counts),
 * so a filter regression that returns the wrong records — not merely the wrong
 * number of them — is caught. Fixture ids: 7, 8, 9 (see tests/resources/events.json).
 */
class EventsFilterTest extends AbstractApiTestCase
{
    protected static string $requestPath = '/api/v2/events';

    #[DataProvider('getEventsProvider')]
    public function testGetEvents(array $query, array $expectedIds, ?string $message = null): void
    {
        $response = $this->get($query);

        $this->assertMemberIds($expectedIds, $response, 'entityId', message: $message ?? '');
    }

    public static function getEventsProvider(): iterable
    {
        yield 'unfiltered' => [[], [7, 8, 9]];

        // BooleanFilter on publicAccess (7 and 9 are public, 8 is not).
        yield 'publicAccess=true' => [['publicAccess' => 'true'], [7, 9]];
        yield 'publicAccess=false' => [['publicAccess' => 'false'], [8]];

        // DateRangeFilter on occurrences.start.
        yield 'occurrences in 21st century' => [
            ['occurrences.start[between]' => static::formatDateTime('2001-01-01').'..'.static::formatDateTime('2100-01-01')],
            [7, 8, 9],
            'Every fixture event has an occurrence in the 21st century',
        ];
        yield 'occurrences in 2026' => [
            ['occurrences.start[between]' => static::formatDateTime('2026-01-01').'..'.static::formatDateTime('2026-12-31')],
            [9],
            'Only event 9 has an occurrence in 2026',
        ];

        // DateRangeFilter on updated (default operator: gte).
        yield 'updated on or after 2024' => [
            ['updated' => static::formatDateTime('2024-01-01')],
            [7, 8, 9],
            'All events were updated on or after 2024-01-01',
        ];
        yield 'updated after 2100' => [
            ['updated[gte]' => static::formatDateTime('2100-01-01')],
            [],
            'No events updated after 2100',
        ];

        // F7: occurrences is a plain object (NOT `nested`) in production, so range
        // clauses on occurrences.start and occurrences.end are evaluated
        // independently across the flattened arrays. Event 8 has occurrences
        // (start 2024-12-07 / end 2024-12-07) and (start 2024-11-08 / end 2024-11-08):
        // NO single occurrence has start >= 2024-12-01 AND end <= 2024-11-30, yet the
        // event still matches. Pinning this makes a future switch to `nested` mapping
        // a conscious, BC-visible decision.
        yield 'F7: start/end satisfied by different occurrences' => [
            [
                'occurrences.start[gte]' => static::formatDateTime('2024-12-01'),
                'occurrences.end[lte]' => static::formatDateTime('2024-11-30'),
            ],
            [8],
            'Non-nested occurrences: event 8 matches although no single occurrence satisfies both bounds',
        ];

        // IdFilter on organizer.entityId (7 and 8 belong to organizer 9; 9 to organizer 11).
        yield 'organizer 9' => [['organizer.entityId' => 9], [7, 8]];
        yield 'organizer 11' => [['organizer.entityId' => 11], [9]];

        // MatchFilter on location.name (`name` is a `text` field → token match).
        yield 'location somewhere' => [['location.name' => 'somewhere'], [9], 'Event 9 is at "Somewhere"'];
        yield 'location another place' => [['location.name' => 'Another place'], []];

        // TagFilter. In production `tags` is a keyword field (see event-database-imports
        // Mappings/Event) — matching is exact, case-sensitive and whole-value (no tokenisation).
        yield 'tag ITKDev' => [['tags' => 'ITKDev'], [7, 9], 'Events tagged "ITKDev" (exact keyword match)'];
        yield 'tag itkdev (wrong case)' => [['tags' => 'itkdev'], [], 'Tag matching is case-sensitive'];
        yield 'tag aros' => [['tags' => 'aros'], [7, 8, 9], 'All fixture events are tagged "aros"'];
        yield 'tag for-boern' => [['tags' => 'for-boern'], [9], 'A hyphenated tag matches as a whole value'];
        yield 'tag boern (substring)' => [['tags' => 'boern'], [], 'No substring/token match on a keyword field'];
        yield 'tag itkdevelopment' => [['tags' => 'itkdevelopment'], []];

        // Combined filters.
        yield 'occurrences in 2026 AND tagged aros' => [
            [
                'occurrences.start[between]' => static::formatDateTime('2026-01-01').'..'.static::formatDateTime('2026-12-31'),
                'tags' => 'aros',
            ],
            [9],
            'Event 9 is the only 2026 event also tagged "aros"',
        ];
    }
}
