<?php

/**
 * @file
 * Cover item data provider.
 *
 * @see https://api-platform.com/docs/core/data-providers/
 */

namespace App\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Api\Dto\Event;

/**
 * Class CoverItemDataProvider.
 */
final class EventRepresentationProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $event = new Event();
        $event->setId(1);

        return $event;
    }
}
