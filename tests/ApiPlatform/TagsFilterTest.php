<?php

namespace App\Tests\ApiPlatform;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test that filtering tags work as expected.
 */
class TagsFilterTest extends AbstractApiTestCase
{
    protected static string $requestPath = '/api/v2/tags';

    #[DataProvider('getTagsProvider')]
    public function testGetTags(array $query, int $expectedCount, ?string $message = null): void
    {
        $message ??= '';

        $response = $this->get($query);

        $data = $response->toArray();
        $this->assertArrayHasKey('hydra:member', $data, $message);
        $this->assertCount($expectedCount, $data['hydra:member'], $message);
    }

    public static function getTagsProvider(): iterable
    {
        // Unfiltered.
        yield [[], 5];

        // MatchFilter on name.
        yield [['name' => 'aros'], 1, 'Tag named "aros"'];
        yield [['name' => 'Koncert'], 1];
        yield [['name' => 'nonexistent'], 0];

        // MatchFilter on vocabulary.
        yield [['vocabulary' => 'aarhusguiden'], 5, 'All fixture tags belong to the aarhusguiden vocabulary'];
        yield [['vocabulary' => 'feeds'], 0];
    }
}
