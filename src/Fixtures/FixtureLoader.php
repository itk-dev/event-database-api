<?php

namespace App\Fixtures;

use App\Service\IndexInterface;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FixtureLoader
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly IndexInterface $index,
        private readonly Client $client,
    ) {
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ClientResponseException
     * @throws ServerExceptionInterface
     * @throws \HttpException
     * @throws ServerResponseException
     * @throws MissingParameterException
     * @throws \Exception
     */
    public function process(string $indexName, string $url): void
    {
        $items = $this->download($url);

        $this->deleteIndex($indexName);
        $this->createIndex($indexName);
        $this->indexItems($indexName, $items);
    }

    /**
     * Download data as JSON from a given URL.
     *
     * @param string $url
     *    The URL from which to download the data
     *
     * @return array
     *    The downloaded data as an associative array
     *
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws \HttpException
     */
    private function download(string $url): array
    {
        // Load from local file if using "file" URL scheme.
        if (preg_match('~^file://(?<path>/.+)$~', $url, $matches)) {
            $path = $matches['path'];
            if (!is_readable($path)) {
                throw new \HttpException('Unable to load fixture data');
            }
            $data = json_decode(file_get_contents($path), true);
            if (empty($data)) {
                throw new \HttpException('Unable to load fixture data');
            }

            return $data;
        }

        $response = $this->httpClient->request('GET', $url);

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            throw new \HttpException('Unable to download fixture data');
        }

        return $response->toArray();
    }

    /**
     * Index items in Elasticsearch.
     *
     * @param string $indexName
     *    The name of the index in Elasticsearch where the items should be indexed
     * @param array $items
     *   The items to be indexed in Elasticsearch. Each item should be an associative array.
     *
     * @throws \Exception
     *   If unable to add an item to the index
     */
    private function indexItems(string $indexName, array $items): void
    {
        foreach ($items as $item) {
            $params = [
                'index' => $indexName,
                'body' => $item,
            ];
            if (isset($item['entityId'])) {
                $params['id'] = $item['entityId'];
            }
            try {
                // No other places in this part of the frontend should index data, hence it's not in the index service.
                $response = $this->client->index($params);

                if (!in_array($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_CREATED, Response::HTTP_NO_CONTENT])) {
                    throw new \Exception('Unable to add item to index', $response->getStatusCode());
                }
            } catch (ClientResponseException|MissingParameterException|ServerResponseException $e) {
                throw new \Exception($e->getMessage(), $e->getCode(), $e);
            }
        }
    }

    /**
     * Creates an index with the given name if it does not already exist.
     *
     * @param string $indexName
     *   The name of the index
     *
     * @throws ClientResponseException
     *   If an error occurs during the Elasticsearch client request
     * @throws MissingParameterException
     *   If the required parameter is missing
     * @throws ServerResponseException
     *   If the server returns an error during the Elasticsearch request
     */
    private function createIndex(string $indexName): void
    {
        if (!$this->index->indexExists($indexName)) {
            // This creation of the index is not in den index service as this is the only place it should be used. In
            // production and in many cases, you should connect to the index managed by the backend (imports).
            $this->client->indices()->create([
                'index' => $indexName,
                'body' => [
                    'settings' => [
                        'number_of_shards' => 5,
                        'number_of_replicas' => 0,
                    ],
                ],
            ]);
        }
    }

    /**
     * Deletes an index with the given name if it exists.
     *
     * @param string $indexName
     *   The name of the index
     *
     * @throws ClientResponseException
     *   If an error occurs during the Elasticsearch client request
     * @throws MissingParameterException
     *   If the required parameter is missing
     * @throws ServerResponseException
     *   If the server returns an error during the Elasticsearch request
     */
    private function deleteIndex(string $indexName): void
    {
        if ($this->index->indexExists($indexName)) {
            // This creation of the index is not in den index service as this is the only place it should be used. In
            // production and in many cases, you should connect to the index managed by the backend (imports).
            $this->client->indices()->delete([
                'index' => $indexName,
            ]);
        }
    }
}
