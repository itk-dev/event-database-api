<?php

namespace App\Tests\ApiPlatform;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test that filtering occurrences work as expected.
 */
class OccurrencesFilterTest extends AbstractApiTestCase
{
    protected static string $requestPath = '/api/v2/occurrences';

    #[DataProvider('getOccurrencesProvider')]
    public function testGetOccurrences(array $query, int $expectedCount, ?string $message = null): void
    {
        $message ??= '';

        $response = $this->get($query);

        $data = $response->toArray();
        $this->assertArrayHasKey('hydra:member', $data, $message);
        $this->assertCount($expectedCount, $data['hydra:member'], $message);
    }

    public static function getOccurrencesProvider(): iterable
    {
        // Unfiltered.
        yield [[], 3];

        // DateRangeFilter on start.
        yield [
            ['start[between]' => static::formatDateTime('2024-12-01').'..'.static::formatDateTime('2024-12-31')],
            2,
            'Occurrences starting in December 2024',
        ];

        yield [
            ['start[between]' => static::formatDateTime('2024-11-01').'..'.static::formatDateTime('2024-11-30')],
            1,
            'Occurrences starting in November 2024',
        ];

        // DateRangeFilter on end.
        yield [
            ['end[between]' => static::formatDateTime('2024-12-07').'..'.static::formatDateTime('2024-12-09')],
            2,
            'Occurrences ending around 2024-12-08',
        ];

        // MatchFilter on event.title. `title` is a `text` field in production (see
        // event-database-imports Mappings/Event), so ES tokenises it and this is a word
        // match — all three fixture events share the tokens "ITKDev/test/event".
        // To narrow we'd need a token unique to a single record.
        yield [
            ['event.title' => 'ITKDev'],
            3,
            'All fixture events have "ITKDev" in the title',
        ];

        yield [
            ['event.title' => 'totallyuniqueword'],
            0,
            'Token absent from all titles returns no occurrences',
        ];

        // MatchFilter on event.organizer.name.
        yield [
            ['event.organizer.name' => 'ITKDev'],
            3,
            'Occurrences organized by ITKDev',
        ];

        // MatchFilter on event.location.name.
        yield [
            ['event.location.name' => 'ITK Development'],
            3,
            'Occurrences at ITK Development',
        ];

        // IdFilter on event.organizer.entityId.
        yield [
            ['event.organizer.entityId' => 9],
            3,
        ];

        yield [
            ['event.organizer.entityId' => 99],
            0,
        ];

        // IdFilter on event.location.entityId.
        yield [
            ['event.location.entityId' => 4],
            3,
        ];

        // TagFilter on event.tags — keyword field, exact/case-sensitive match.
        yield [
            ['event.tags' => 'ITKDev'],
            1,
            'Occurrences for events tagged "ITKDev"',
        ];

        yield [
            ['event.tags' => 'unknown-tag'],
            0,
        ];
    }
}
