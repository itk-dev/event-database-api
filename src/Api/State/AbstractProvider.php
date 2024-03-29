<?php

namespace App\Api\State;

use ApiPlatform\Elasticsearch\Filter\FilterInterface;
use ApiPlatform\Elasticsearch\Filter\SortFilterInterface;
use ApiPlatform\Metadata\Operation;
use App\Model\FilterTypes;
use App\Model\IndexNames;
use App\Service\IndexInterface;
use Psr\Container\ContainerInterface;

/**
 * AbstractProvider class.
 */
abstract class AbstractProvider
{
    protected const PAGE_SIZE_FALLBACK = 10;

    public function __construct(
        protected readonly IndexInterface $index,
        protected readonly ContainerInterface $filterLocator,
    ) {
    }

    /**
     * Retrieves the filters to be applied to the operation.
     *
     * @param operation $operation
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
            FilterTypes::Filters->value => [],
            FilterTypes::Sort->value => [],
        ];

        if (!is_null($resourceFilters)) {
            foreach ($resourceFilters as $filterId) {
                $filter = $this->getFilterById($filterId);

                if ($filter instanceof FilterInterface) {
                    $data = $filter->apply([], IndexNames::Events->value, $operation, $context);

                    if (!empty($data)) {
                        if ($filter instanceof SortFilterInterface) {
                            $outputFilters[FilterTypes::Sort->value][] = $data;
                        } else {
                            $outputFilters[FilterTypes::Filters->value][] = $data;
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
        return (($context['filters']['page'] ?? 1) - 1) * $this->getImagesPerPage($context);
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
    protected function getImagesPerPage(array $context): int
    {
        return $context['filters']['itemsPerPage'] ?? self::PAGE_SIZE_FALLBACK;
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
