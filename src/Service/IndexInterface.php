<?php

namespace App\Service;

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
     */
    public function indexExists(string $indexName): bool;

    public function get(string $indexName, int $id): array;

    public function getAll(string $indexName, int $from = 0, int $size = 10): array;
}
