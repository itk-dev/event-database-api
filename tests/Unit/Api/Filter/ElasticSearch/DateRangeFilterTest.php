<?php

declare(strict_types=1);

namespace App\Tests\Unit\Api\Filter\ElasticSearch;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use App\Api\Filter\ElasticSearch\DateRangeFilter;
use App\Model\DateLimit;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Pins DateRangeFilter::apply() query DSL and its error behaviour. `between`
 * produces exclusive `gt`/`lt` bounds — a consumer-visible semantic. Malformed
 * and unknown-operator input, when the filter is configured with
 * `throwOnInvalid: true`, throws ApiPlatform's InvalidArgumentException — mapped
 * to HTTP 400 via `exception_to_status` — rather than leaking a native throwable
 * as a 500. With `throwOnInvalid: false` the clause is skipped instead.
 */
final class DateRangeFilterTest extends TestCase
{
    use FilterFactoryMockTrait;

    private const string RESOURCE = \App\Api\Dto\Event::class;

    /**
     * @param array{limit: DateLimit, throwOnInvalid: bool}[] $config
     */
    private function newFilter(array $properties, array $config): DateRangeFilter
    {
        [$names, $meta, $resolver] = $this->filterDependencies();

        return new DateRangeFilter($names, $meta, $resolver, null, $properties, $config);
    }

    // Goal: apply() maps the fallback and explicit operators to `range` DSL, and
    // expands `between` to exclusive gt/lt bounds.
    #[DataProvider('applyProvider')]
    public function testApplyEmitsExpectedDsl(array $filters, array $expected): void
    {
        $filter = $this->newFilter(
            ['updated' => 'gte'],
            ['gte' => ['limit' => DateLimit::gte, 'throwOnInvalid' => true]],
        );

        $this->assertSame($expected, $filter->apply([], self::RESOURCE, null, ['filters' => $filters]));
    }

    public static function applyProvider(): iterable
    {
        yield 'no filters → empty' => [[], []];
        yield 'empty string → skipped' => [['updated' => ''], []];

        // Bare value → the configured fallback operator (gte).
        yield 'fallback operator' => [
            ['updated' => '2024-01-01T00:00:00+00:00'],
            ['range' => ['updated' => ['gte' => '2024-01-01T00:00:00+00:00']]],
        ];

        // Explicit operator via `updated[gt]=…`.
        yield 'explicit gt' => [
            ['updated' => ['gt' => '2024-01-01T00:00:00+00:00']],
            ['range' => ['updated' => ['gt' => '2024-01-01T00:00:00+00:00']]],
        ];

        // `between` expands to EXCLUSIVE gt/lt bounds — a consumer-visible semantic.
        yield 'between → exclusive gt/lt' => [
            ['updated' => ['between' => '2024-01-01T00:00:00+00:00..2024-02-01T00:00:00+00:00']],
            ['range' => ['updated' => [
                'gt' => '2024-01-01T00:00:00+00:00',
                'lt' => '2024-02-01T00:00:00+00:00',
            ]]],
        ];
    }

    // Goal: a malformed `between` value throws ApiPlatform's InvalidArgumentException
    // (mapped to HTTP 400) rather than a native throwable that leaks as a 500.
    public function testMalformedBetweenThrows(): void
    {
        $filter = $this->newFilter(
            ['updated' => 'gte'],
            ['gte' => ['limit' => DateLimit::gte, 'throwOnInvalid' => true]],
        );

        // No ".." separator → invalid range.
        $this->expectException(InvalidArgumentException::class);
        $filter->apply([], self::RESOURCE, null, ['filters' => ['updated' => ['between' => '2024-01-01T00:00:00+00:00']]]);
    }

    // Goal: an unknown operator (`updated[foo]=…`) throws ApiPlatform's
    // InvalidArgumentException instead of the native \Error it used to leak.
    public function testUnknownOperatorThrows(): void
    {
        $filter = $this->newFilter(
            ['updated' => 'gte'],
            ['gte' => ['limit' => DateLimit::gte, 'throwOnInvalid' => true]],
        );

        $this->expectException(InvalidArgumentException::class);
        $filter->apply([], self::RESOURCE, null, ['filters' => ['updated' => ['foo' => 'x']]]);
    }

    // Goal: `throwOnInvalid: false` makes invalid input skip the clause (empty DSL)
    // instead of throwing — the flag is consulted for both invalid paths.
    #[DataProvider('invalidInputProvider')]
    public function testThrowOnInvalidFalseSkipsInvalidInput(array $filters): void
    {
        $filter = $this->newFilter(
            ['updated' => 'gte'],
            ['gte' => ['limit' => DateLimit::gte, 'throwOnInvalid' => false]],
        );

        $this->assertSame([], $filter->apply([], self::RESOURCE, null, ['filters' => $filters]));
    }

    public static function invalidInputProvider(): iterable
    {
        yield 'malformed between' => [['updated' => ['between' => 'no-separator']]];
        yield 'unknown operator' => [['updated' => ['foo' => 'x']]];
    }

    // Goal: getDescription() advertises the default parameter plus every
    // [operator] variant — the surface the OpenAPI spec gate protects.
    public function testGetDescriptionExposesEveryOperatorVariant(): void
    {
        $filter = $this->newFilter(
            ['updated' => 'gte'],
            ['gte' => ['limit' => DateLimit::gte, 'throwOnInvalid' => true]],
        );

        $description = $filter->getDescription(self::RESOURCE);

        foreach (['updated', 'updated[between]', 'updated[gt]', 'updated[gte]', 'updated[lt]', 'updated[lte]'] as $key) {
            $this->assertArrayHasKey($key, $description, $key.' should be described');
        }
    }
}
