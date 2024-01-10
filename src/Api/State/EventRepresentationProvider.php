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
    // @TODO: should we create enum with 5,10,15,20
    public const PAGE_SIZE = 10;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            $filters = $this->getFilters($operation, $context);
            $data = $this->index->getAll(IndexNames::Events->value, $filters, $context['filters']['page'] * self::PAGE_SIZE, self::PAGE_SIZE);

            return $data['hits'];
        }

        return (object) $this->index->get(IndexNames::Events->value, $uriVariables['id'])['_source'];
    }
}
