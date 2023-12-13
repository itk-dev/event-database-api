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
     * @throws \JsonException
     */
    private function parseResponse(Elasticsearch $response): array
    {
        $json = (string) $response->getBody();
        $document = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return $document['_source'];
    }
}
