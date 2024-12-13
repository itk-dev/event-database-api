<?php

namespace App\Api\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Exception\IndexException;
use App\Model\IndexNames;
use App\Service\ElasticSearch\ElasticSearchPaginator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

final class OrganizationRepresentationProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws IndexException
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            $filters = $this->getFilters($operation, $context);
            $offset = $this->calculatePageOffset($context);
            $limit = $this->getItemsPerPage($context);
            $results = $this->index->getAll(IndexNames::Organizations->value, $filters, $offset, $limit);

            return new ElasticSearchPaginator($results, $limit, $offset);
        }

        try {
            return [$this->index->get(IndexNames::Organizations->value, $uriVariables['id'])['_source']];
        } catch (IndexException $e) {
            if (404 === $e->getCode()) {
                return null;
            }

            throw $e;
        }
    }
}
