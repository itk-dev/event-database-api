<?php

namespace App\Api\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Model\IndexNames;
use App\Service\IndexInterface;

final class EventRepresentationProvider implements ProviderInterface
{
    // @TODO: should we create enum with 5,10,15,20
    public const PAGE_SIZE = 10;

    public function __construct(
        private readonly IndexInterface $index,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            $data = $this->index->getAll(IndexNames::Events->value, 0, self::PAGE_SIZE);

            return $data['hits'];
        }

        return (object) $this->index->get(IndexNames::Events->value, $uriVariables['id'])['_source'];
    }
}
