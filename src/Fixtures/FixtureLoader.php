<?php

namespace App\Fixtures;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FixtureLoader
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly Client $client
    ) {
    }

    public function process(string $indexName, string $url): void
    {
        $items = $this->download($url);

        $configuration = [
            'index' => $indexName,
            'body' => [
                'settings' => [
                    'number_of_shards' => 5,
                    'number_of_replicas' => 0,
                ],
            ],
        ];

        try {
            $response = $this->client->indices()->create($configuration);
        } catch (ClientResponseException|MissingParameterException|ServerResponseException $e) {
            // Ignore index exists error.
        }

        foreach ($items as $item) {
            $params = [
                'index' => $indexName,
                'id' => $item['entityId'],
                'body' => $item,
            ];
            try {
                $response = $this->client->index($params);

                if (!in_array($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_CREATED, Response::HTTP_NO_CONTENT])) {
                    throw new \Exception('Unable to add item to index', $response->getStatusCode());
                }
            } catch (ClientResponseException|MissingParameterException|ServerResponseException $e) {
                throw new \Exception($e->getMessage(), $e->getCode(), $e);
            }
        }
    }

    private function download($url): array
    {
        $response = $this->httpClient->request('GET', $url);

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            throw new \HttpException('Unable to download fixture data');
        }

        return json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }
}
