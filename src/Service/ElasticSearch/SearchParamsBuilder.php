<?php

namespace App\Service\ElasticSearch;

use App\Model\FilterType;
use App\Model\IndexName;

/**
 * Builds the Elasticsearch `search` request parameters (query + pagination +
 * sort) from the compiled filter clauses.
 *
 * Extracted from ElasticSearchIndex as a pure, dependency-free service so the
 * query-DSL construction can be unit-tested without a live Elasticsearch.
 */
class SearchParamsBuilder
{
    /**
     * @param array<string, array<int, mixed>> $filters compiled clauses keyed by FilterType
     *
     * @return array<string, mixed>
     */
    public function buildParams(string $indexName, array $filters, int $from, int $size): array
    {
        $params = [
            'index' => $indexName,
            'body' => [
                'query' => [
                    'match_all' => (object) [],
                ],
                'size' => $size,
                'from' => $from,
                // @TODO: make a proper sort filter to allow client to set sort direction
                'sort' => $this->buildSort($indexName),
            ],
        ];

        $body = $this->buildBody($filters);
        if ([] !== $body) {
            $params['body']['query'] = $body;
        }

        return $params;
    }

    /**
     * Combines the filter clauses into a single `bool`/`must` query.
     *
     * @param array<string, array<int, mixed>> $filters
     *
     * @return array<string, mixed>
     */
    private function buildBody(array $filters): array
    {
        $body = [];
        foreach ($filters[FilterType::Filters->value] as $filter) {
            if (!array_key_exists('bool', $body)) {
                $body['bool'] = ['must' => []];
            }
            // Ensure that associative arrays and lists are not combined with keys "0","1" etc. in the final json.
            // So we need to loop over lists to ensure keys are "reset" in the final body statement.
            if (array_is_list($filter)) {
                foreach ($filter as $val) {
                    $body['bool']['must'][] = $val;
                }
            } else {
                $body['bool']['must'][] = $filter;
            }
        }

        return $body;
    }

    /**
     * The per-index sort configuration.
     *
     * @return array<int|string, mixed>
     */
    private function buildSort(string $indexName): array
    {
        return match (IndexName::tryFrom($indexName)) {
            IndexName::Events => [
                '_score',
                ['title.keyword' => ['order' => 'asc']],
            ],
            IndexName::DailyOccurrences, IndexName::Occurrences => [
                'start' => [
                    'order' => 'asc',
                    'format' => 'strict_date_optional_time_nanos',
                ],
            ],
            IndexName::Tags, IndexName::Vocabularies, IndexName::Locations, IndexName::Organizations => [
                '_score',
                ['name.keyword' => ['order' => 'asc']],
            ],
            default => [
                '_score',
            ],
        };
    }
}
