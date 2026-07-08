<?php

namespace App\Tests\ApiPlatform;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test that filtering daily_occurrences works as expected.
 *
 * Assertions pin the exact set of matching `entityId`s. Fixture ids mirror the
 * occurrences index: 10/11 belong to event 8, 12 to event 7.
 */
class DailyOccurrencesFilterTest extends AbstractApiTestCase
{
    protected static string $requestPath = '/api/v2/daily_occurrences';

    #[DataProvider('getDailyOccurrencesProvider')]
    public function testGetDailyOccurrences(array $query, array $expectedIds, ?string $message = null): void
    {
        $response = $this->get($query);

        $this->assertMemberIds($expectedIds, $response, 'entityId', message: $message ?? '');
    }

    public static function getDailyOccurrencesProvider(): iterable
    {
        yield 'unfiltered' => [[], [10, 11, 12]];

        // BooleanFilter on event.publicAccess (DailyOccurrence-specific).
        yield 'event publicAccess=true' => [
            ['event.publicAccess' => 'true'],
            [10, 11, 12],
            'All daily occurrences belong to public events',
        ];

        // DateRangeFilter on start.
        yield 'start in December 2024' => [
            ['start[between]' => static::formatDateTime('2024-12-01').'..'.static::formatDateTime('2024-12-31')],
            [10, 12],
        ];

        // MatchFilter on event.title (token-based — see OccurrencesFilterTest comment).
        yield 'event title token ITKDev' => [['event.title' => 'ITKDev'], [10, 11, 12]];

        // IdFilter on event.organizer.entityId.
        yield 'event organizer 9' => [['event.organizer.entityId' => 9], [10, 11, 12]];

        // TagFilter on event.tags — keyword field, exact/case-sensitive match.
        yield 'event tag ITKDev' => [['event.tags' => 'ITKDev'], [12]];
        yield 'event tag unknown' => [['event.tags' => 'unknown-tag'], []];
    }
}
