<?php

namespace App\Api\State;

use ApiPlatform\Elasticsearch\Filter\FilterInterface;
use ApiPlatform\Elasticsearch\Filter\OrderFilter;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Model\IndexNames;
use App\Service\IndexInterface;
use ApiPlatform\Elasticsearch\Filter\AbstractFilter;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;


final class EventRepresentationProvider implements ProviderInterface
{
    // @TODO: should we create enum with 5,10,15,20
    public const PAGE_SIZE = 10;

    public function __construct(
        private readonly IndexInterface $index,
        private readonly ContainerInterface $filterLocator,
    ) {
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $resourceFilters = $operation?->getFilters();
        $orderFilters = [];
        $outputFilters = [];

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

        if ($operation instanceof CollectionOperationInterface) {
            $data = $this->index->getAll(IndexNames::Events->value, $outputFilters, $context['filters']['page'], self::PAGE_SIZE);

            return $data['hits'];
        }

        return (object) $this->index->get(IndexNames::Events->value, $uriVariables['id'])['_source'];
    }
}
