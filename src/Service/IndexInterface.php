<?php

namespace App\Service;

use App\Exception\IndexException;
use App\Service\ElasticSearch\SearchResults;

interface IndexInterface
{
    /**
     * Checks if the given index exists.
     *
     * @param string $indexName
     *   The name of the index to check
     *
     * @return bool
     *   True if the index exists, false otherwise
     *
     * @throws IndexException
     */
    public function indexExists(string $indexName): bool;

    /**
     * Retrieves information from the specified index based on the provided ID.
     *
     * @param string $indexName
     *   The name of the index to retrieve information from
     * @param int $id
     *   The ID of the document to retrieve
     *
     * @return array
     *   The retrieved document as an array
     *
     * @throws IndexException
     */
    public function get(string $indexName, int $id): array;

    /**
     * Retrieves documents from the specified index with optional filters and pagination.
     *
     * @param string $indexName
     *   The name of the index to retrieve data from
     * @param array $filters
     *   An array of filters to apply to the retrieved documents keyed by FilterTypes. Default is an empty array.
     * @param int $from
     *   The starting page. Default is 0.
     * @param int $size
     *   The maximum number of records to retrieve. Default is 10.
     *
     * @return SearchResults
     *   An array containing the retrieved documents
     *
     * @throws IndexException
     */
    public function getAll(string $indexName, array $filters = [], int $from = 0, int $size = 10): SearchResults;
}
