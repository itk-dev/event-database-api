<?php

namespace App\Api\State;

use ApiPlatform\Elasticsearch\Filter\FilterInterface;
use ApiPlatform\Elasticsearch\Filter\SortFilterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PaginationOptions;
use App\Model\FilterType;
use App\Model\IndexName;
use App\Service\IndexInterface;
use Psr\Container\ContainerInterface;

/**
 * AbstractProvider class.
 */
abstract class AbstractProvider
{
    protected const int MAX_PAGE_SIZE_FALLBACK = 20;

    public function __construct(
        protected readonly IndexInterface $index,
        protected readonly ContainerInterface $filterLocator,
        protected readonly PaginationOptions $paginationOptions,
    ) {
    }

    /**
     * Retrieves the filters to be applied to the operation.
     *
     * @param Operation $operation
     *   The operation to retrieve the filters for
     * @param array $context
     *   Additional context for filter application
     *
     * @return array
     *   An array of filters to be applied to the operation
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function getFilters(Operation $operation, array $context = []): array
    {
        $resourceFilters = $operation->getFilters();
        $outputFilters = [
            FilterType::Filters->value => [],
            FilterType::Sort->value => [],
        ];

        if (!is_null($resourceFilters)) {
            foreach ($resourceFilters as $filterId) {
                $filter = $this->getFilterById($filterId);

                if ($filter instanceof FilterInterface) {
                    $data = $filter->apply([], IndexName::Events->value, $operation, $context);

                    if (!empty($data)) {
                        if ($filter instanceof SortFilterInterface) {
                            $outputFilters[FilterType::Sort->value][] = $data;
                        } else {
                            $outputFilters[FilterType::Filters->value][] = $data;
                        }
                    }
                }
            }
        }

        return $outputFilters;
    }

    /**
     * Calculates the offset for a paginated result based on the provided context.
     *
     * @param array $context
     *   The context containing the pagination information
     *
     * @return int
     *   The calculated offset value
     */
    protected function calculatePageOffset(array $context): int
    {
        return (($context['filters']['page'] ?? 1) - 1) * $this->getItemsPerPage($context);
    }

    /**
     * Retrieves the number of items per page.
     *
     * @param array $context
     *   The context containing the pagination information
     *
     * @return int
     *   The number of items per page as determined by the context
     */
    protected function getItemsPerPage(array $context): int
    {
        $itemsPerPage = $context['filters']['itemsPerPage'] ?? $this->paginationOptions->getItemsPerPage();
        $maxItemsPerPage = $this->paginationOptions->getMaximumItemsPerPage() ?? self::MAX_PAGE_SIZE_FALLBACK;

        return min($itemsPerPage, $maxItemsPerPage);
    }

    /**
     * Retrieves a filter based on the provided filter ID.
     *
     * @param string $filterId
     *   The ID of the filter to retrieve
     *
     * @return FilterInterface|null
     *   The filter instance if found, otherwise null
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function getFilterById(string $filterId): ?FilterInterface
    {
        return $this->filterLocator->has($filterId) ? $this->filterLocator->get($filterId) : null;
    }
}
