<?php

namespace App\Tests\ApiPlatform;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test that filtering daily_occurrences work as expected.
 */
class DailyOccurrencesFilterTest extends AbstractApiTestCase
{
    protected static string $requestPath = '/api/v2/daily_occurrences';

    #[DataProvider('getDailyOccurrencesProvider')]
    public function testGetDailyOccurrences(array $query, int $expectedCount, ?string $message = null): void
    {
        $message ??= '';

        $response = $this->get($query);

        $data = $response->toArray();
        $this->assertArrayHasKey('hydra:member', $data, $message);
        $this->assertCount($expectedCount, $data['hydra:member'], $message);
    }

    public static function getDailyOccurrencesProvider(): iterable
    {
        // Unfiltered.
        yield [[], 3];

        // BooleanFilter on event.publicAccess (DailyOccurrence-specific).
        yield [
            ['event.publicAccess' => 'true'],
            3,
            'All fixture events have publicAccess=true',
        ];

        // DateRangeFilter on start.
        yield [
            ['start[between]' => static::formatDateTime('2024-12-01').'..'.static::formatDateTime('2024-12-31')],
            2,
        ];

        // MatchFilter on event.title (token-based — see OccurrencesFilterTest comment).
        yield [
            ['event.title' => 'ITKDev'],
            3,
        ];

        // IdFilter on event.organizer.entityId.
        yield [
            ['event.organizer.entityId' => 9],
            3,
        ];

        // TagFilter on event.tags.
        yield [
            ['event.tags' => 'itkdev'],
            1,
        ];

        yield [
            ['event.tags' => 'unknown-tag'],
            0,
        ];
    }
}
