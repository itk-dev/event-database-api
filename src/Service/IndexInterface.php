<?php

namespace App\Service;

interface IndexInterface
{
    /**
     * Checks if the given index exists.
     *
     * @param string $indexName
     *   The name of the index to check.
     *
     * @return bool
     *   True if the index exists, false otherwise.
     */
    public function indexExists(string $indexName): bool;
}
