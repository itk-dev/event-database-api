<?php

namespace App\Model;

/**
 * Represents the results of a search operation.
 */
final class SearchResults
{
    public function __construct(
        public readonly array $hits,
        public readonly int $total,
    ) {
    }
}
