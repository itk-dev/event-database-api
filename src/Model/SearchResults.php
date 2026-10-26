<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Represents the results of a search operation.
 */
final readonly class SearchResults
{
    public function __construct(
        public array $hits,
        public int $total,
    ) {
    }
}
