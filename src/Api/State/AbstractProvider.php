<?php

namespace App\Api\State;

use ApiPlatform\Elasticsearch\Filter\FilterInterface;
use ApiPlatform\Elasticsearch\Filter\OrderFilter;
use ApiPlatform\Metadata\Operation;
use App\Model\IndexNames;
use App\Service\IndexInterface;
use Psr\Container\ContainerInterface;

abstract class AbstractProvider
{
    public function __construct(
        protected readonly IndexInterface $index,
        protected readonly ContainerInterface $filterLocator,
    ) {
    }

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

                    $outputFilters[$filterId] = $filter->apply([], IndexNames::Events->value, $operation, $context);
                }
            }

            foreach ($orderFilters as $filterId => $orderFilter) {
                $outputFilters['orderFilters'] ??= [];
                $outputFilters['orderFilters'][$filterId] = $orderFilter->apply([], IndexNames::Events->value, $operation, $context);
            }
        }

        return $outputFilters;
    }
}
