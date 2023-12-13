<?php

namespace App\Service;

use App\Exception\IndexException;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
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
}
