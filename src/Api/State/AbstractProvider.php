<?php

namespace App\Api\State;

use ApiPlatform\Elasticsearch\Filter\FilterInterface;
use ApiPlatform\Elasticsearch\Filter\OrderFilter;
use ApiPlatform\Metadata\Operation;
use App\Model\IndexNames;
use App\Service\IndexInterface;
use Psr\Container\ContainerInterface;

/**
 * AbstractProvider class.
 */
abstract class AbstractProvider
{
    protected const PAGE_SIZE = 10;

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
        $orderFilters = [];
        $outputFilters = [];

        if (!is_null($resourceFilters)) {
            foreach ($resourceFilters as $filterId) {
                $filter = $this->filterLocator->has($filterId) ? $this->filterLocator->get($filterId) : null;

                if ($filter instanceof FilterInterface) {
                    // Apply the OrderFilter after every.
                    if ($filter instanceof OrderFilter) {
                        $orderFilters[$filterId] = $filter;
                        continue;
                    }

                    $data = $filter->apply([], IndexNames::Events->value, $operation, $context);
                    if (!empty($data)) {
                        $outputFilters[$filterId] = $data;
                    }
                }
            }

            foreach ($orderFilters as $filterId => $orderFilter) {
                $outputFilters['orderFilters'] ??= [];
                $data = $orderFilter->apply([], IndexNames::Events->value, $operation, $context);
                if (!empty($data)) {
                    $outputFilters['orderFilters'][$filterId] = $data;
                }
            }
        }

        return $outputFilters;
    }

    /**
     * Calculates the offset for a paginated result based on the provided context.
     *
     * @param array $context
     *   The context containing the pagination filters
     *
     * @return int
     *   The calculated offset value
     */
    protected function calculatePageOffset(array $context): int
    {
        return (($context['filters']['page'] ?? 1) - 1) * self::PAGE_SIZE;
    }
}
