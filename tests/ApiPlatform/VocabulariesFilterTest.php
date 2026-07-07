<?php

namespace App\Tests\ApiPlatform;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test that filtering vocabularies work as expected.
 */
class VocabulariesFilterTest extends AbstractApiTestCase
{
    protected static string $requestPath = '/api/v2/vocabularies';

    #[DataProvider('getVocabulariesProvider')]
    public function testGetVocabularies(array $query, int $expectedCount, ?string $message = null): void
    {
        $message ??= '';

        $response = $this->get($query);

        $data = $response->toArray();
        $this->assertArrayHasKey('hydra:member', $data, $message);
        $this->assertCount($expectedCount, $data['hydra:member'], $message);
    }

    public static function getVocabulariesProvider(): iterable
    {
        // Unfiltered.
        yield [[], 2];

        // MatchFilter on name.
        yield [['name' => 'aarhusguiden'], 1];
        yield [['name' => 'feeds'], 1];
        yield [['name' => 'nonexistent'], 0];

        // MatchFilter on tags.
        yield [['tags' => 'aros'], 1, 'aarhusguiden vocabulary contains the "aros" tag'];
        yield [['tags' => 'unknown-tag'], 0];
    }
}
