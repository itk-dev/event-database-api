<?php

namespace App\Tests\Unit\Api\Filter\ElasticSearch;

use App\Api\Filter\ElasticSearch\TagFilter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Pins TagFilter::apply() query DSL — a comma-split `terms` clause with a boost.
 * The emitted term values are verbatim (no lowercasing/normalisation): matching
 * against the production `keyword` mapping is exact and case-sensitive.
 */
class TagFilterTest extends TestCase
{
    use FilterFactoryMockTrait;

    private const RESOURCE = 'App\Api\Dto\Event';

    // Goal: apply() emits a comma-split `terms` clause with verbatim values (no
    // lowercasing), so matching the production keyword mapping stays exact.
    #[DataProvider('applyProvider')]
    public function testApplyEmitsExpectedDsl(array $filters, array $expected): void
    {
        [$names, $meta, $resolver] = $this->filterDependencies();
        $filter = new TagFilter($names, $meta, $resolver, null, ['tags' => null]);

        self::assertSame($expected, $filter->apply([], self::RESOURCE, null, ['filters' => $filters]));
    }

    public static function applyProvider(): iterable
    {
        yield 'no filters → empty' => [[], []];
        yield 'empty string → skipped' => [['tags' => ''], []];
        yield 'single tag, verbatim casing' => [['tags' => 'ITKDev'], ['terms' => ['tags' => ['ITKDev'], 'boost' => 1.0]]];
        yield 'comma list is split' => [['tags' => 'aros,ITKDev'], ['terms' => ['tags' => ['aros', 'ITKDev'], 'boost' => 1.0]]];
        // A hyphenated tag is a single whole value, not split.
        yield 'hyphenated tag is one value' => [['tags' => 'for-boern'], ['terms' => ['tags' => ['for-boern'], 'boost' => 1.0]]];
    }

    // Goal: getDescription() advertises the property as a collection parameter.
    public function testGetDescriptionShape(): void
    {
        [$names, $meta, $resolver] = $this->filterDependencies();
        $filter = new TagFilter($names, $meta, $resolver, null, ['tags' => null]);

        $description = $filter->getDescription(self::RESOURCE);

        self::assertArrayHasKey('tags', $description);
        self::assertTrue($description['tags']['is_collection']);
    }
}
