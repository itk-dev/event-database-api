<?php

namespace App\Service;

use ApiPlatform\State\Pagination\PaginatorInterface;
use App\Model\SearchResults;

/**
 * Paginator for Elasticsearch.
 */
final class ElasticSearchPaginator implements \IteratorAggregate, PaginatorInterface
{
    public function __construct(
        private readonly SearchResults $results,
        private readonly int $limit,
        private readonly int $offset,
    ) {
    }

    public function count(): int
    {
        return $this->results->total;
    }

    public function getLastPage(): float
    {
        if (0 >= $this->limit) {
            return 1.;
        }

        return ceil($this->getTotalItems() / $this->limit) ?: 1.;
    }

    public function getTotalItems(): float
    {
        return (float) $this->results->total;
    }

    public function getCurrentPage(): float
    {
        if (0 >= $this->limit) {
            return 1.;
        }

        return floor($this->offset / $this->limit) + 1.;
    }

    public function getItemsPerPage(): float
    {
        return (float) $this->limit;
    }

    public function getIterator(): \Traversable
    {
        foreach ($this->results->hits as $hit) {
            yield $hit;
        }
    }
}
