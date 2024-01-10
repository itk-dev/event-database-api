<?php

namespace App\Api\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Model\IndexNames;

final class OrganizationRepresentationProvider extends AbstractProvider implements ProviderInterface
{
    // @TODO: should we create enum with 5,10,15,20
    public const PAGE_SIZE = 10;

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            $filters = $this->getFilters($operation, $context);
            $from = $this->calculatePageOffset($context);

            return $this->index->getAll(IndexNames::Organization->value, $filters, $from, self::PAGE_SIZE);
        }

        return [$this->index->get(IndexNames::Organization->value, $uriVariables['id'])['_source']];
    }
}
