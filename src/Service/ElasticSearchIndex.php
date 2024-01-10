<?php

namespace App\Service;

use App\Exception\IndexException;
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

    /**
     * @throws IndexException
     */
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

    /**
     * @throws ClientResponseException
     * @throws ServerResponseException
     * @throws MissingParameterException
     * @throws \JsonException
     */
    public function get(string $indexName, int $id): array
    {
        $params = [
            'index' => $indexName,
            'id' => $id,
        ];

        // @TODO: check status codes.
        $response = $this->client->get($params);

        return $this->parseResponse($response);
    }

    /**
     * @throws ClientResponseException
     * @throws ServerResponseException
     * @throws \JsonException
     */
    public function getAll(string $indexName, array $filters = [], int $from = 0, int $size = 10): array
    {
        $params = [
            'index' => $indexName,
            'body' => [
                'query' => [
                    'match_all' => (object) [],
                ],
                'size' => $size,
                'from' => $from,
                'sort' => [],
            ],
        ];

        $body = [];
        foreach ($filters as $filter) {
            // @TODO: add order filters to sort
            $body += $filter;
        }

        if (!empty($body)) {
            $params['body']['query'] = $body;
        }

        $response = $this->client->search($params);
        $results = $this->parseResponse($response);

        return $this->cleanUpHits($results);
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
    private function cleanUpHits(array $results): array
    {
        $hits = [];
        foreach ($results['hits']['hits'] as $hit) {
            $hits[] = $hit['_source'];
        }

        return $hits;
    }
}
