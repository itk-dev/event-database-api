<?php

declare(strict_types=1);

namespace App\Tests\Unit\Api\Filter\ElasticSearch;

use App\Api\Filter\ElasticSearch\IdFilter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Pins IdFilter::apply() query DSL. Unlike Boolean/TagFilter, each property
 * yields its own `terms` clause appended to a list, with the boost nested inside
 * the clause.
 */
final class IdFilterTest extends TestCase
{
    use FilterFactoryMockTrait;

    private const string RESOURCE = \App\Api\Dto\Event::class;

    // Goal: apply() appends one `terms` clause per property (boost nested inside)
    // and comma-splits multi-value ids.
    #[DataProvider('applyProvider')]
    public function testApplyEmitsExpectedDsl(array $properties, array $filters, array $expected): void
    {
        [$names, $meta, $resolver] = $this->filterDependencies();
        $filter = new IdFilter($names, $meta, $resolver, null, $properties);

        $this->assertSame($expected, $filter->apply([], self::RESOURCE, null, ['filters' => $filters]));
    }

    public static function applyProvider(): iterable
    {
        $props = ['organizer.entityId' => null, 'location.entityId' => null];

        yield 'no filters → empty' => [$props, [], []];
        yield 'empty string → skipped' => [$props, ['organizer.entityId' => ''], []];
        yield 'single id' => [
            $props,
            ['organizer.entityId' => '9'],
            [['terms' => ['organizer.entityId' => ['9'], 'boost' => 1.0]]],
        ];
        yield 'comma list is split' => [
            $props,
            ['organizer.entityId' => '9,11'],
            [['terms' => ['organizer.entityId' => ['9', '11'], 'boost' => 1.0]]],
        ];
        yield 'two properties → two clauses' => [
            $props,
            ['organizer.entityId' => '9', 'location.entityId' => '4'],
            [
                ['terms' => ['organizer.entityId' => ['9'], 'boost' => 1.0]],
                ['terms' => ['location.entityId' => ['4'], 'boost' => 1.0]],
            ],
        ];
    }

    // Goal: getDescription() advertises the property as a collection parameter.
    public function testGetDescriptionShape(): void
    {
        [$names, $meta, $resolver] = $this->filterDependencies();
        $filter = new IdFilter($names, $meta, $resolver, null, ['organizer.entityId' => null]);

        $description = $filter->getDescription(self::RESOURCE);

        $this->assertArrayHasKey('organizer.entityId', $description);
        $this->assertTrue($description['organizer.entityId']['is_collection']);
    }
}
