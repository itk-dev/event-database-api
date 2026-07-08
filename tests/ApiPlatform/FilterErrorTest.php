<?php

namespace App\Tests\ApiPlatform;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Verify the contract for malformed filter input.
 *
 * The DateRangeFilter on the Event, Occurrence, DailyOccurrence, Location and
 * Organization resources is configured with `throwOnInvalid: true`, so a
 * malformed value (unparseable `between`, unknown operator) surfaces as a
 * client error (4xx) rather than a 5xx leak: the filter throws ApiPlatform's
 * InvalidArgumentException, mapped to HTTP 400 via `exception_to_status`. A
 * non-date value passes the filter but is rejected by Elasticsearch, coming
 * back as an ElasticIndexException (also mapped to 400).
 */
class FilterErrorTest extends AbstractApiTestCase
{
    protected static string $requestPath = '/api/v2/events';

    #[DataProvider('invalidDateRangeProvider')]
    public function testInvalidDateRangeReturnsClientError(string $path, array $query, string $message): void
    {
        $response = $this->get($query, $path);
        $statusCode = $response->getStatusCode();
        $this->assertGreaterThanOrEqual(400, $statusCode, $message.': '.$statusCode);
        $this->assertLessThan(500, $statusCode, 'Filter errors must not leak as 5xx — '.$message);
    }

    public static function invalidDateRangeProvider(): iterable
    {
        // Missing '..' separator → DateRangeFilter throws InvalidArgumentException.
        yield 'events: missing separator' => [
            '/api/v2/events',
            ['occurrences.start[between]' => '2024-01-01T00:00:00+00:00'],
            'Between filter without ".." separator',
        ];

        // Too many '..' segments.
        yield 'events: three segments' => [
            '/api/v2/events',
            ['occurrences.start[between]' => '2024-01-01T00:00:00+00:00..2024-06-01T00:00:00+00:00..2024-12-31T00:00:00+00:00'],
            'Between filter with three segments',
        ];

        // Unknown operator → DateRangeFilter can no longer resolve the DateLimit
        // case and throws InvalidArgumentException (used to be a raw \Error → 500).
        yield 'events: unknown operator' => [
            '/api/v2/events',
            ['occurrences.start[whenever]' => '2024-01-01T00:00:00+00:00'],
            'Unknown date range operator',
        ];

        // Non-date value passes the filter but Elasticsearch cannot parse it →
        // ElasticIndexException (mapped to 400).
        yield 'events: non-date value' => [
            '/api/v2/events',
            ['occurrences.start[gte]' => 'not-a-date'],
            'Non-date value for a date range filter',
        ];

        yield 'occurrences: missing separator' => [
            '/api/v2/occurrences',
            ['start[between]' => '2024-01-01T00:00:00+00:00'],
            'Between filter on occurrences without ".." separator',
        ];

        yield 'daily_occurrences: missing separator' => [
            '/api/v2/daily_occurrences',
            ['start[between]' => '2024-01-01T00:00:00+00:00'],
            'Between filter on daily_occurrences without ".." separator',
        ];
    }
}
