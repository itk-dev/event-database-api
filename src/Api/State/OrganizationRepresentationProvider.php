<?php

namespace App\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Model\IndexNames;
use App\Service\IndexInterface;
use Symfony\Component\HttpFoundation\Request;

final class OrganizationRepresentationProvider implements ProviderInterface
{
    public function __construct(
        private readonly IndexInterface $index,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        return match ($operation->getMethod()) {
            Request::METHOD_GET => [$this->index->get(IndexNames::Organization->value, $uriVariables['id'])],
            // @TODO: Handle not support methods correctly
            default => []
        };
    }
}
