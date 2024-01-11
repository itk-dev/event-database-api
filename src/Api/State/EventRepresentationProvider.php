<?php

namespace App\Api\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Model\IndexNames;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

final class EventRepresentationProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \App\Exception\IndexException
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        // @TODO: should we create enum with 5,10,15,20
        // Get page size from context.

        if ($operation instanceof CollectionOperationInterface) {
            $filters = $this->getFilters($operation, $context);
            $from = $this->calculatePageOffset($context);

            return $this->index->getAll(IndexNames::Events->value, $filters, $from, self::PAGE_SIZE);
        }

        return (object) $this->index->get(IndexNames::Events->value, $uriVariables['id'])['_source'];
    }
}
