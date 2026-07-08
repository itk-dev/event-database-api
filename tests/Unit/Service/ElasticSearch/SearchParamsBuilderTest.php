<?php

namespace App\Tests\Unit\Service\ElasticSearch;

use App\Model\FilterType;
use App\Model\IndexName;
use App\Service\ElasticSearch\SearchParamsBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Pins the Elasticsearch `search` request that SearchParamsBuilder produces —
 * the default match_all query, how filter clauses combine into `bool`/`must`,
 * and the per-index sort — without a live Elasticsearch.
 */
class SearchParamsBuilderTest extends TestCase
{
    private function noFilters(): array
    {
        return [FilterType::Filters->value => [], FilterType::Sort->value => []];
    }

    /**
     * @param array<int, mixed> $clauses
     */
    private function withFilters(array $clauses): array
    {
        return [FilterType::Filters->value => $clauses, FilterType::Sort->value => []];
    }

    // Goal: with no filters the query defaults to match_all and pagination/sort are set.
    public function testDefaultsToMatchAllWithPagination(): void
    {
        $params = (new SearchParamsBuilder())->buildParams(IndexName::Events->value, $this->noFilters(), 20, 5);

        self::assertSame(IndexName::Events->value, $params['index']);
        self::assertEquals(['match_all' => (object) []], $params['body']['query']);
        self::assertSame(5, $params['body']['size']);
        self::assertSame(20, $params['body']['from']);
        self::assertArrayHasKey('sort', $params['body']);
    }

    // Goal: a single associative clause (e.g. a MatchFilter hit) is wrapped in bool/must.
    public function testSingleClauseWrappedInBoolMust(): void
    {
        $params = (new SearchParamsBuilder())->buildParams(
            IndexName::Events->value,
            $this->withFilters([['match' => ['title' => 'x']]]),
            0,
            10,
        );

        self::assertSame(['bool' => ['must' => [['match' => ['title' => 'x']]]]], $params['body']['query']);
    }

    // Goal: a list-shaped clause (e.g. IdFilter output) is flattened into must, not
    // nested under numeric keys.
    public function testListClauseIsFlattenedIntoMust(): void
    {
        $params = (new SearchParamsBuilder())->buildParams(
            IndexName::Events->value,
            $this->withFilters([[['terms' => ['organizer.entityId' => ['9'], 'boost' => 1.0]]]]),
            0,
            10,
        );

        self::assertSame(
            ['bool' => ['must' => [['terms' => ['organizer.entityId' => ['9'], 'boost' => 1.0]]]]],
            $params['body']['query'],
        );
    }

    // Goal: multiple clauses accumulate under a single bool/must.
    public function testMultipleClausesCombine(): void
    {
        $params = (new SearchParamsBuilder())->buildParams(
            IndexName::Events->value,
            $this->withFilters([
                ['match' => ['title' => 'x']],
                ['terms' => ['tags' => ['aros'], 'boost' => 1.0]],
            ]),
            0,
            10,
        );

        self::assertSame([
            ['match' => ['title' => 'x']],
            ['terms' => ['tags' => ['aros'], 'boost' => 1.0]],
        ], $params['body']['query']['bool']['must']);
    }

    // Goal: each index gets its documented sort; unknown indexes fall back to _score.
    #[DataProvider('sortProvider')]
    public function testSortPerIndex(string $index, array $expectedSort): void
    {
        $params = (new SearchParamsBuilder())->buildParams($index, $this->noFilters(), 0, 10);

        self::assertSame($expectedSort, $params['body']['sort']);
    }

    public static function sortProvider(): iterable
    {
        yield 'events' => [IndexName::Events->value, ['_score', ['title.keyword' => ['order' => 'asc']]]];
        yield 'occurrences' => [IndexName::Occurrences->value, ['start' => ['order' => 'asc', 'format' => 'strict_date_optional_time_nanos']]];
        yield 'tags' => [IndexName::Tags->value, ['_score', ['name.keyword' => ['order' => 'asc']]]];
        yield 'unknown → _score' => ['not_an_index', ['_score']];
    }
}
