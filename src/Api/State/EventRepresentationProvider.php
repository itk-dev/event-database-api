<?php

namespace App\Api\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Model\IndexNames;
use App\Service\ElasticSearch\ElasticSearchPaginator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

final class EventRepresentationProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \App\Exception\IndexException
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ElasticSearchPaginator|array|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            $filters = $this->getFilters($operation, $context);
            $offset = $this->calculatePageOffset($context);
            $limit = $this->getImagesPerPage($context);
            $results = $this->index->getAll(IndexNames::Events->value, $filters, $offset, $limit);

            return new ElasticSearchPaginator($results, $limit, $offset);
        }

        return [$this->index->get(IndexNames::Events->value, $uriVariables['id'])['_source']];
    }
}
