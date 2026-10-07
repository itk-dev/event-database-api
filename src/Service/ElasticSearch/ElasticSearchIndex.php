<?php

namespace App\Service\ElasticSearch;

use App\Exception\IndexException;
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
        private readonly SearchParamsBuilder $paramsBuilder,
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
        }

        return $this->getByCustomIdField($indexName, $id, $indexField);
    }

    private function getById(string $indexName, int|string $id): array
    {
        $params = [
            'index' => $indexName,
            'id' => (string) $id,
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
        $params = $this->paramsBuilder->buildParams($indexName, $filters, $from, $size);

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
}
