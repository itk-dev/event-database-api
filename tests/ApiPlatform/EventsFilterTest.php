<?php

namespace ApiPlatform;

use App\Tests\ApiPlatform\AbstractApiTestCase;
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

        // Test DateRangeFilter.
        yield [
            ['occurrences.start[between]' => implode('..', [
                (new \DateTimeImmutable('2001-01-01'))->format(\DateTimeImmutable::ATOM),
                (new \DateTimeImmutable('2100-01-01'))->format(\DateTimeImmutable::ATOM),
            ])],
            3,
            'Events in 21st century',
        ];

        yield [
            ['occurrences.start[between]' => implode('..', [
                (new \DateTimeImmutable('2001-01-01'))->format(\DateTimeImmutable::ATOM),
                (new \DateTimeImmutable('2100-01-01'))->format(\DateTimeImmutable::ATOM),
            ])],
            3,
            'Events in 21st century',
        ];

        yield [
            ['occurrences.start[between]' => static::formatDateTime('2026-01-01').'..'.static::formatDateTime('2026-12-31')],
            1,
            'Events in 2026',
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

        // Test TagFilter.
        yield [
            ['tags' => 'itkdev'],
            2,
            'Events tagged with "itkdev"',
        ];

        yield [
            ['tags' => 'itkdevelopment'],
            0,
            'Events tagged with "itkdevelopment"',
        ];

        // @todo Does tags filtering use the tag slug or name?
        // yield [
        //   ['tags' => 'for børn'],
        //   0,
        //   'Events tagged with "for børn"',
        // ];
        //
        // yield [
        //   ['tags' => 'for-boern'],
        //   1,
        //   'Events tagged with "for-boern"',
        // ];

        // @todo It seems that filtering in tags use som sort of "contains word"
        // stuff, i.e. we can match the tag "for-boern" by filtering on "boern"
        // or on "for" – but not on "for-boern" …
        yield [
            ['tags' => 'boern'],
            1,
            'Events tagged with "boern"',
        ];

        yield [
            ['tags' => 'for'],
            2,
            'Events tagged with "for"',
        ];

        // Combined filters.
        yield [
            [
                'occurrences.start[between]' => static::formatDateTime('2026-01-01').'..'.static::formatDateTime('2026-12-31'),
                'tags' => 'itkdev',
            ],
            1,
            'Events in 2026 tagged with "itkdev"',
        ];

        yield [
            [
                'title' => 'cykel',
            ],
            0,
        ];

        yield [
            [
                'title' => 'bicycle',
            ],
            1,
        ];
    }
}
