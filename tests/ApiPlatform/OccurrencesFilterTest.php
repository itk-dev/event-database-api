<?php

declare(strict_types=1);

namespace App\Tests\ApiPlatform;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test that filtering occurrences works as expected.
 *
 * Assertions pin the exact set of matching occurrence `entityId`s. Fixture ids:
 * 10 (start 2024-12-07, event 8), 11 (start 2024-11-08, event 8),
 * 12 (start 2024-12-08, event 7). See tests/resources/occurrences.json.
 */
final class OccurrencesFilterTest extends AbstractApiTestCase
{
    protected static string $requestPath = '/api/v2/occurrences';

    #[DataProvider('getOccurrencesProvider')]
    public function testGetOccurrences(array $query, array $expectedIds, ?string $message = null): void
    {
        $response = $this->get($query);

        $this->assertMemberIds($expectedIds, $response, 'entityId', message: $message ?? '');
    }

    public static function getOccurrencesProvider(): iterable
    {
        yield 'unfiltered' => [[], [10, 11, 12]];

        // DateRangeFilter on start.
        yield 'start in December 2024' => [
            ['start[between]' => self::formatDateTime('2024-12-01').'..'.self::formatDateTime('2024-12-31')],
            [10, 12],
            'Occurrences 10 and 12 start in December 2024',
        ];
        yield 'start in November 2024' => [
            ['start[between]' => self::formatDateTime('2024-11-01').'..'.self::formatDateTime('2024-11-30')],
            [11],
            'Only occurrence 11 starts in November 2024',
        ];

        // DateRangeFilter on end.
        yield 'end around 2024-12-08' => [
            ['end[between]' => self::formatDateTime('2024-12-07').'..'.self::formatDateTime('2024-12-09')],
            [10, 12],
            'Occurrences 10 and 12 end within 2024-12-07..2024-12-09',
        ];

        // MatchFilter on event.title. `title` is a `text` field in production (see
        // event-database-imports Mappings/Event), so ES tokenises it and this is a word
        // match — all three fixture events share the token "ITKDev".
        yield 'event title token ITKDev' => [
            ['event.title' => 'ITKDev'],
            [10, 11, 12],
            'All fixture events have "ITKDev" in the title',
        ];
        yield 'event title absent token' => [['event.title' => 'totallyuniqueword'], []];

        // MatchFilter on event.organizer.name / event.location.name (all events share these).
        yield 'event organizer name ITKDev' => [['event.organizer.name' => 'ITKDev'], [10, 11, 12]];
        yield 'event location name' => [['event.location.name' => 'ITK Development'], [10, 11, 12]];

        // IdFilter on event.organizer.entityId / event.location.entityId.
        yield 'event organizer 9' => [['event.organizer.entityId' => 9], [10, 11, 12]];
        yield 'event organizer 99' => [['event.organizer.entityId' => 99], []];
        yield 'event location 4' => [['event.location.entityId' => 4], [10, 11, 12]];

        // TagFilter on event.tags — keyword field, exact/case-sensitive match. Only
        // occurrence 12 belongs to event 7, which carries the "ITKDev" tag.
        yield 'event tag ITKDev' => [['event.tags' => 'ITKDev'], [12], 'Occurrence 12 belongs to the ITKDev-tagged event'];
        yield 'event tag unknown' => [['event.tags' => 'unknown-tag'], []];
    }
}
