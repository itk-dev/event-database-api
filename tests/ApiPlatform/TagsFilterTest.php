<?php

namespace App\Tests\ApiPlatform;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test that filtering tags works as expected.
 *
 * Tag is a true API Platform resource identified by `slug`, so assertions pin
 * the exact set of matching slugs. Fixture slugs: aros, theoceanraceaarhus,
 * koncert, for-boern, itkdev (see tests/resources/tags.json).
 */
class TagsFilterTest extends AbstractApiTestCase
{
    protected static string $requestPath = '/api/v2/tags';

    #[DataProvider('getTagsProvider')]
    public function testGetTags(array $query, array $expectedSlugs, ?string $message = null): void
    {
        $response = $this->get($query);

        $this->assertMemberIds($expectedSlugs, $response, 'slug', message: $message ?? '');
    }

    public static function getTagsProvider(): iterable
    {
        yield 'unfiltered' => [[], ['aros', 'theoceanraceaarhus', 'koncert', 'for-boern', 'itkdev']];

        // MatchFilter on name (`name` is a `text` field → token match); slug is the identity.
        yield 'name aros' => [['name' => 'aros'], ['aros'], 'Tag named "aros"'];
        yield 'name Koncert' => [['name' => 'Koncert'], ['koncert'], 'Tag "Koncert" has slug "koncert"'];
        yield 'name nonexistent' => [['name' => 'nonexistent'], []];

        // MatchFilter on vocabulary.
        yield 'vocabulary aarhusguiden' => [
            ['vocabulary' => 'aarhusguiden'],
            ['aros', 'theoceanraceaarhus', 'koncert', 'for-boern', 'itkdev'],
            'All fixture tags belong to the aarhusguiden vocabulary',
        ];
        yield 'vocabulary feeds' => [['vocabulary' => 'feeds'], []];
    }
}
