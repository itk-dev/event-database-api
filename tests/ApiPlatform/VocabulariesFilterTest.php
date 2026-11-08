<?php

declare(strict_types=1);

namespace App\Tests\ApiPlatform;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test that filtering vocabularies works as expected.
 *
 * Vocabulary is a true API Platform resource identified by `slug`, so assertions
 * pin the exact set of matching slugs. Fixture slugs: aarhusguiden, feeds
 * (see tests/resources/vocabularies.json).
 */
final class VocabulariesFilterTest extends AbstractApiTestCase
{
    protected static string $requestPath = '/api/v2/vocabularies';

    #[DataProvider('getVocabulariesProvider')]
    public function testGetVocabularies(array $query, array $expectedSlugs, ?string $message = null): void
    {
        $response = $this->get($query);

        $this->assertMemberIds($expectedSlugs, $response, 'slug', message: $message ?? '');
    }

    public static function getVocabulariesProvider(): iterable
    {
        yield 'unfiltered' => [[], ['aarhusguiden', 'feeds']];

        // MatchFilter on name (`name` is a `text` field → token match); slug is the identity.
        yield 'name aarhusguiden' => [['name' => 'aarhusguiden'], ['aarhusguiden']];
        yield 'name feeds' => [['name' => 'feeds'], ['feeds']];
        yield 'name nonexistent' => [['name' => 'nonexistent'], []];

        // MatchFilter on tags — only aarhusguiden contains the "aros" tag.
        yield 'tags aros' => [['tags' => 'aros'], ['aarhusguiden'], 'aarhusguiden vocabulary contains the "aros" tag'];
        yield 'tags unknown' => [['tags' => 'unknown-tag'], []];
    }
}
