<?php

namespace App\Service\ElasticSearch;

use App\Exception\IndexException;
use App\Model\FilterType;
use App\Model\IndexName;
use App\Model\SearchResults;
use App\Service\IndexInterface;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Symfony\Component\HttpFoundation\Response;

class ElasticSearchIndex implements IndexInterface
{
    public function __construct(
        private readonly Client $client,
    ) {
    }

    public function indexExists(string $indexName): bool
    {
        try {
            /** @var Elasticsearch $response */
            $response = $this->client->indices()->get(['index' => $indexName]);

            return Response::HTTP_OK === $response->getStatusCode();
        } catch (ClientResponseException|ServerResponseException $e) {
            if (Response::HTTP_NOT_FOUND === $e->getCode()) {
                return false;
            }

            throw new ElasticIndexException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function get(string $indexName, int|string $id, string $indexField = 'id'): array
    {
        if ('id' === $indexField) {
            return $this->getById($indexName, $id);
        } else {
            return $this->getByCustomIdField($indexName, $id, $indexField);
        }
    }

    private function getById(string $indexName, int|string $id): array
    {
        $params = [
            'index' => $indexName,
            'id' => $id,
        ];

        try {
            /** @var Elasticsearch $response */
            $response = $this->client->get($params);
            if (Response::HTTP_OK !== $response->getStatusCode()) {
                throw new IndexException('Failed to get document from Elasticsearch', Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            $result = $this->parseResponse($response);
        } catch (ClientResponseException|ServerResponseException|MissingParameterException|\JsonException $e) {
            throw new ElasticIndexException($e->getMessage(), $e->getCode(), $e);
        }

        return $result;
    }

    /**
     * Simulate get for indexes where the "id" field has a different name.
     *
     * The ES client get() requires a 'id' field. To get items from indexes where
     * we have to use search() with a term query.
     *
     * @throws IndexException
     */
    private function getByCustomIdField(string $indexName, int|string $id, string $indexField = 'id'): array
    {
        $params = [
            'index' => $indexName,
            'body' => [
                'query' => [
                    'term' => [
                        $indexField => $id,
                    ],
                ],
            ],
        ];

        try {
            /** @var Elasticsearch $response */
            $response = $this->client->search($params);
            if (Response::HTTP_OK !== $response->getStatusCode()) {
                throw new IndexException('Failed to get document from Elasticsearch', Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            $result = $this->parseResponse($response);
        } catch (ClientResponseException|ServerResponseException|\JsonException $e) {
            throw new ElasticIndexException($e->getMessage(), $e->getCode(), $e);
        }

        if (0 === $result['hits']['total']['value']) {
            // The ES client get() will throw a 404 exception when no document was found.
            throw new IndexException('Not found', 404);
        }

        if (1 < $result['hits']['total']['value']) {
            throw new IndexException('ID search returned multiple hits', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $result['hits']['hits'][0];
    }

    public function getAll(string $indexName, array $filters = [], int $from = 0, int $size = 10): SearchResults
    {
        $params = $this->buildParams($indexName, $filters, $from, $size);

        try {
            /** @var Elasticsearch $response */
            $response = $this->client->search($params);
            if (Response::HTTP_OK !== $response->getStatusCode()) {
                throw new IndexException('Failed to get document from Elasticsearch', Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            $data = $this->parseResponse($response);
        } catch (ClientResponseException|ServerResponseException|\JsonException $e) {
            throw new ElasticIndexException($e->getMessage(), $e->getCode(), $e);
        }

        return new SearchResults(
            hits: $this->extractSourceFromHits($data),
            total: $this->getTotalHits($data),
        );
    }

    /**
     * Builds the parameters for the Elasticsearch search request.
     *
     * @param string $indexName
     *   The name of the index to search in
     * @param array $filters
     *   An array of filters to apply to the search query
     * @param int $from
     *   The starting offset for the search results
     * @param int $size
     *   The maximum number of search results to return
     *
     * @return array
     *   The built parameters for the Elasticsearch search request
     */
    private function buildParams(string $indexName, array $filters, int $from, int $size): array
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
                'sort' => $this->getSort($indexName),
            ],
        ];

        $body = $this->buildBody($filters);
        if (!empty($body)) {
            $params['body']['query'] = $body;
        }

        return $params;
    }

    /**
     * Builds the body for Elasticsearch request using the given filters.
     *
     * @param array $filters
     *   The filters to be included in the body
     *
     * @return array
     *   The built body for Elasticsearch request
     */
    private function buildBody(array $filters): array
    {
        $body = [];
        $combined = (bool) count($filters[FilterType::Filters->value]);
        foreach ($filters[FilterType::Filters->value] as $filter) {
            if ($combined) {
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
            } else {
                $body += $filter;
            }
        }

        return $body;
    }

    /**
     * Parses the response from Elasticsearch and returns it as an array.
     *
     * @param Elasticsearch $response
     *   The Elasticsearch response object
     *
     * @return array
     *   Returns the parsed response as an array
     *
     * @throws \JsonException
     *   Throws a JSON exception if there is an error while parsing the response
     */
    private function parseResponse(Elasticsearch $response): array
    {
        return json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Cleans up hits by extracting the "_source" field from the given results array.
     *
     * @param array $results
     *   The search results from elastic search
     *
     * @return array
     *   The cleaned-up hits
     */
    private function extractSourceFromHits(array $results): array
    {
        $hits = [];
        foreach ($results['hits']['hits'] as $hit) {
            $hits[] = $hit['_source'];
        }

        return $hits;
    }

    /**
     * Retrieves the total number of hits from the given search result.
     *
     * @param array $data
     *   The data array containing the Elasticsearch response
     *
     * @return int
     *   Returns the total number of hits as an integer. If the 'value' key is not present, 0 is returned.
     */
    private function getTotalHits(array $data): int
    {
        return $data['hits']['total']['value'] ?? 0;
    }

    /**
     * Get the sorting configuration for a specific index.
     *
     * This method returns an array containing the sorting configuration based on the given index name.
     * If the index name matches one of the predefined index names, a specific sorting configuration will be returned.
     * Otherwise, an empty array will be returned indicating no sorting is required.
     *
     * @param string $indexName the name of the index
     *
     * @return array the sorting configuration
     */
    private function getSort(string $indexName): array
    {
        // Translates a string or int into the corresponding Enum case, if any.
        // If there is no matching case defined, it will return null.
        $indexName = IndexName::tryFrom($indexName);

        return match ($indexName) {
            IndexName::Events => [
                '_score',
                [
                    'title.keyword' => [
                        'order' => 'asc',
                    ],
                ],
            ],
            IndexName::DailyOccurrences, IndexName::Occurrences => [
                'start' => [
                    'order' => 'asc',
                    'format' => 'strict_date_optional_time_nanos',
                ],
            ],
            IndexName::Tags, IndexName::Vocabularies,IndexName::Locations, IndexName::Organizations => [
                '_score',
                [
                    'name.keyword' => [
                        'order' => 'asc',
                    ],
                ],
            ],
            default => [
                '_score',
            ],
        };
    }
}
