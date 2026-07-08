<?php

namespace App\Tests\Unit\Api\Filter\ElasticSearch;

use App\Api\Filter\ElasticSearch\BooleanFilter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Pins BooleanFilter::apply() query DSL. The value is comma-split and wrapped in
 * a single `terms` clause with a boost; an empty selection emits nothing.
 */
class BooleanFilterTest extends TestCase
{
    use FilterFactoryMockTrait;

    private const RESOURCE = 'App\Api\Dto\Event';

    // Goal: apply() wraps the comma-split value in a single `terms` clause with a
    // boost; unset/empty is skipped and "0" is treated as a real value.
    #[DataProvider('applyProvider')]
    public function testApplyEmitsExpectedDsl(array $filters, array $expected): void
    {
        [$names, $meta, $resolver] = $this->filterDependencies();
        $filter = new BooleanFilter($names, $meta, $resolver, null, ['publicAccess' => null]);

        self::assertSame($expected, $filter->apply([], self::RESOURCE, null, ['filters' => $filters]));
    }

    public static function applyProvider(): iterable
    {
        yield 'no filters → empty' => [[], []];
        yield 'empty string → skipped' => [['publicAccess' => ''], []];
        yield 'true' => [['publicAccess' => 'true'], ['terms' => ['publicAccess' => ['true'], 'boost' => 1.0]]];
        yield 'false' => [['publicAccess' => 'false'], ['terms' => ['publicAccess' => ['false'], 'boost' => 1.0]]];
        // "0" is a real value and must reach ES (regression guard for the old empty() drop).
        yield "'0' reaches ES" => [['publicAccess' => '0'], ['terms' => ['publicAccess' => ['0'], 'boost' => 1.0]]];
        yield 'comma list is split' => [['publicAccess' => 'true,false'], ['terms' => ['publicAccess' => ['true', 'false'], 'boost' => 1.0]]];
    }

    // Goal: getDescription() advertises the property as an optional boolean parameter.
    public function testGetDescriptionShape(): void
    {
        [$names, $meta, $resolver] = $this->filterDependencies();
        $filter = new BooleanFilter($names, $meta, $resolver, null, ['publicAccess' => null]);

        $description = $filter->getDescription(self::RESOURCE);

        self::assertArrayHasKey('publicAccess', $description);
        self::assertSame('bool', $description['publicAccess']['type']);
        self::assertFalse($description['publicAccess']['required']);
    }
}
