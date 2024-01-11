<?php

namespace App\Service;

use App\Exception\IndexException;
use App\Model\FilterTypes;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Symfony\Component\HttpFoundation\Response;

class ElasticSearchIndex implements IndexInterface
{
    public function __construct(
        private readonly Client $client
    ) {
    }

    public function indexExists($indexName): bool
    {
        try {
            /** @var Elasticsearch $response */
            $response = $this->client->indices()->getAlias(['name' => $indexName]);

            return Response::HTTP_OK === $response->getStatusCode();
        } catch (ClientResponseException|ServerResponseException $e) {
            if (Response::HTTP_NOT_FOUND === $e->getCode()) {
                return false;
            }

            throw new IndexException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function get(string $indexName, int $id): array
    {
        $params = [
            'index' => $indexName,
            'id' => $id,
        ];

        try {
            /** @var Elasticsearch $response */
            $response = $this->client->get($params);
            if (Response::HTTP_OK !== $response->getStatusCode()) {
                throw new IndexException('Failed to get document from Elasticsearch', $response->getStatusCode());
            }
            $result = $this->parseResponse($response);
        } catch (ClientResponseException|ServerResponseException|MissingParameterException|\JsonException $e) {
            throw new IndexException($e->getMessage(), $e->getCode(), $e);
        }

        return $result;
    }

    public function getAll(string $indexName, array $filters = [], int $from = 0, int $size = 10): array
    {
        $params = $this->buildParams($indexName, $filters, $from, $size);

        try {
            /** @var Elasticsearch $response */
            $response = $this->client->search($params);
            if (Response::HTTP_OK !== $response->getStatusCode()) {
                throw new IndexException('Failed to get document from Elasticsearch', $response->getStatusCode());
            }
            $results = $this->parseResponse($response);
        } catch (ClientResponseException|ServerResponseException|\JsonException $e) {
            throw new IndexException($e->getMessage(), $e->getCode(), $e);
        }

        return $this->extractSourceFromHits($results);
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
                // @TODO: add order filters to sort results
                'sort' => [],
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
        foreach ($filters[FilterTypes::Filters] as $filter) {
            $body += $filter;
        }

        return $body;
    }

    /**
     * Parses the response from Elasticsearch and returns it as an array.
     *
     * @param elasticsearch $response
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
}
