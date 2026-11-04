<?php

namespace App\Fixtures;

use App\Service\IndexInterface;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Elastic\Elasticsearch\Response\Elasticsearch;
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
        private readonly string $mappingsDir,
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
        if (1 === preg_match('~^file://(?<path>/.+)$~', $url, $matches)) {
            $path = $matches['path'];
            if (!is_readable($path)) {
                throw new \HttpException('Unable to load fixture data');
            }
            $contents = file_get_contents($path);
            if (false === $contents) {
                throw new \HttpException('Unable to load fixture data');
            }
            $data = json_decode($contents, true);
            if (!is_array($data) || [] === $data) {
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
                /** @var Elasticsearch $response */
                $response = $this->client->index($params);

                if (!in_array($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_CREATED, Response::HTTP_NO_CONTENT], true)) {
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
        if ($this->index->indexExists($indexName)) {
            return;
        }

        // This creation of the index is not in the index service as this is the only place it should be used. In
        // production you connect to the index managed by the backend (imports). The mapping applied here is a
        // production-parity copy of the importer's `dynamic: strict` mappings (tests/resources/mappings/), so filter
        // tests exercise real Elasticsearch field semantics (keyword vs text) instead of dynamic-mapping artefacts.
        $mappingFile = $this->mappingsDir.'/'.$indexName.'.json';
        if (!is_readable($mappingFile)) {
            throw new \RuntimeException(sprintf('Missing production-parity mapping for index "%s" (expected %s). Export it from event-database-imports (src/Model/Indexing/Mappings); the test harness must never create an index with dynamic mapping.', $indexName, $mappingFile));
        }
        $contents = file_get_contents($mappingFile);
        if (false === $contents) {
            throw new \RuntimeException(sprintf('Unable to read mapping file %s', $mappingFile));
        }
        $mappings = json_decode($contents, true);
        if (!is_array($mappings)) {
            throw new \RuntimeException(sprintf('Invalid mapping JSON in %s', $mappingFile));
        }

        $this->client->indices()->create([
            'index' => $indexName,
            'body' => [
                'settings' => [
                    'number_of_shards' => 5,
                    'number_of_replicas' => 0,
                ],
                'mappings' => $mappings,
            ],
        ]);
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
