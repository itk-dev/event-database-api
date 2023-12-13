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
use App\Service\IndexInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CoverItemDataProvider.
 */
final class EventRepresentationProvider implements ProviderInterface
{
    public function __construct(
        private readonly IndexInterface $index,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        return match ($operation->getMethod()) {
            Request::METHOD_GET => $this->getEvent($uriVariables),
            // @TODO: Handle not support methods correctly
            default => new Event()
        };
    }

    private function getEvent($uriVariables): Event
    {
        $id = $uriVariables['id'];

        // @TODO: find index name form path?
        $data = $this->index->get('events', $id);

        // @TODO: usa mapper to do this if possible.
        $event = new Event();
        $event->setId($data['entityId'])
            ->setTitle($data['title'])
            ->setExcerpt($data['excerpt'])
            ->setDescription($data['description'])
            ->setUrl($data['url'])
            ->setTicketUrl($data['ticketUrl'])
            ->setPublic($data['public']);

        return $event;
    }
}
