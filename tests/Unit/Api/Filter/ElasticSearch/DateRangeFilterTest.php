<?php

namespace App\Tests\Unit\Api\Filter\ElasticSearch;

use App\Api\Filter\ElasticSearch\DateRangeFilter;
use App\Model\DateLimit;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Pins DateRangeFilter::apply() query DSL and its (currently un-mapped) error
 * behaviour. `between` produces exclusive `gt`/`lt` bounds — a consumer-visible
 * semantic. The malformed-input and unknown-operator cases document current
 * behaviour: both surface as uncaught throwables (they leak as HTTP 500 today).
 */
class DateRangeFilterTest extends TestCase
{
    use FilterFactoryMockTrait;

    private const RESOURCE = 'App\Api\Dto\Event';

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

        self::assertSame($expected, $filter->apply([], self::RESOURCE, null, ['filters' => $filters]));
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

    // Goal: pin that a malformed `between` value is currently an uncaught throwable
    // (the error-contract fix will turn this into a 4xx).
    public function testMalformedBetweenThrows(): void
    {
        $filter = $this->newFilter(
            ['updated' => 'gte'],
            ['gte' => ['limit' => DateLimit::gte, 'throwOnInvalid' => true]],
        );

        // No ".." separator → invalid range. Currently an uncaught \InvalidArgumentException
        // (leaks as HTTP 500; the error-contract fix will map this to 4xx).
        $this->expectException(\InvalidArgumentException::class);
        $filter->apply([], self::RESOURCE, null, ['filters' => ['updated' => ['between' => '2024-01-01T00:00:00+00:00']]]);
    }

    // Goal: pin the second, distinct error leak — an unknown operator hits a native
    // \Error — so the error-contract fix addresses both paths.
    public function testUnknownOperatorThrowsError(): void
    {
        $filter = $this->newFilter(
            ['updated' => 'gte'],
            ['gte' => ['limit' => DateLimit::gte, 'throwOnInvalid' => true]],
        );

        // `updated[foo]=…` resolves DateLimit::{foo} → a native \Error (undefined enum
        // case). Distinct from the malformed-between path; also leaks as HTTP 500 today.
        $this->expectException(\Error::class);
        $filter->apply([], self::RESOURCE, null, ['filters' => ['updated' => ['foo' => 'x']]]);
    }

    // Goal: document that the `throwOnInvalid` config flag is dead — invalid input
    // throws whether it is true or false.
    #[DataProvider('throwOnInvalidProvider')]
    public function testThrowOnInvalidConfigIsNotConsulted(bool $throwOnInvalid): void
    {
        // `throwOnInvalid` is stored in the config but never read: invalid input throws
        // regardless of its value. This pins that the flag is currently dead.
        $filter = $this->newFilter(
            ['updated' => 'gte'],
            ['gte' => ['limit' => DateLimit::gte, 'throwOnInvalid' => $throwOnInvalid]],
        );

        $this->expectException(\InvalidArgumentException::class);
        $filter->apply([], self::RESOURCE, null, ['filters' => ['updated' => ['between' => 'no-separator']]]);
    }

    public static function throwOnInvalidProvider(): iterable
    {
        yield 'throwOnInvalid=true' => [true];
        yield 'throwOnInvalid=false' => [false];
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
            self::assertArrayHasKey($key, $description, $key.' should be described');
        }
    }
}
