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
    public function testGetEvents(array $query, int $expectedCount): void
    {
        $response = $this->get($query);

        $data = $response->toArray();
        $this->assertArrayHasKey('hydra:member', $data);
        $this->assertCount($expectedCount, $data['hydra:member']);
    }

    public static function getEventsProvider(): iterable
    {
        yield [
            [],
            3,
        ];

        yield [
            ['publicAccess' => 'true'],
            2,
        ];

        yield [
            ['publicAccess' => 'false'],
            1,
        ];
    }
}
