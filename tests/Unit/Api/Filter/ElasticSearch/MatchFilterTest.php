<?php

declare(strict_types=1);

namespace App\Tests\Unit\Api\Filter\ElasticSearch;

use App\Api\Filter\ElasticSearch\MatchFilter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Pins the exact Elasticsearch query DSL emitted by MatchFilter::apply() and the
 * shape of getDescription(), independent of Elasticsearch. These lock the
 * consumer-visible query contract so a change to the filter (or an API Platform
 * upgrade) is caught.
 */
final class MatchFilterTest extends TestCase
{
    use FilterFactoryMockTrait;

    private const string RESOURCE = \App\Api\Dto\Event::class;

    /**
     * Goal: apply() emits the exact `match` DSL — a single hit is returned bare,
     * multiple hits as a list, and unset/empty values are skipped.
     *
     * @param array<string, mixed> $filters
     */
    #[DataProvider('applyProvider')]
    public function testApplyEmitsExpectedDsl(array $properties, array $filters, mixed $expected): void
    {
        [$names, $meta, $resolver] = $this->filterDependencies();
        $filter = new MatchFilter($names, $meta, $resolver, null, $properties);

        $this->assertSame($expected, $filter->apply([], self::RESOURCE, null, ['filters' => $filters]));
    }

    public static function applyProvider(): iterable
    {
        $props = ['title' => null, 'organizer.name' => null];

        yield 'no filters → empty' => [$props, [], []];
        yield 'unset property → empty' => [$props, ['somethingElse' => 'x'], []];
        yield 'empty string → skipped' => [$props, ['title' => ''], []];
        yield 'empty array → skipped' => [$props, ['title' => []], []];

        // A single match returns the bare clause (not wrapped in a list).
        yield 'single match' => [$props, ['title' => 'bicycle'], ['match' => ['title' => 'bicycle']]];

        // "0" is a real value and must reach Elasticsearch (regression guard for
        // the old empty() drop).
        yield "'0' reaches ES" => [$props, ['title' => '0'], ['match' => ['title' => '0']]];

        // Two matches are returned as a list.
        yield 'two matches → list' => [
            $props,
            ['title' => 'a', 'organizer.name' => 'b'],
            [['match' => ['title' => 'a']], ['match' => ['organizer.name' => 'b']]],
        ];
    }

    // Goal: getDescription() advertises the property as an optional string parameter
    // (the surface the OpenAPI spec gate protects).
    public function testGetDescriptionShape(): void
    {
        [$names, $meta, $resolver] = $this->filterDependencies();
        $filter = new MatchFilter($names, $meta, $resolver, null, ['title' => null]);

        $description = $filter->getDescription(self::RESOURCE);

        $this->assertArrayHasKey('title', $description);
        $this->assertSame('title', $description['title']['property']);
        $this->assertSame('string', $description['title']['type']);
        $this->assertFalse($description['title']['required']);
    }
}
