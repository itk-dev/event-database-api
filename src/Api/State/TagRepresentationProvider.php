<?php

namespace App\Api\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Api\Dto\Tag;
use App\Exception\IndexException;
use App\Model\IndexName;
use App\Model\SearchResults;
use App\Service\ElasticSearch\ElasticSearchPaginator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

final class TagRepresentationProvider extends AbstractProvider implements ProviderInterface
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
            $results = $this->index->getAll(IndexName::Tags->value, $filters, $offset, $limit);

            $tags = [];
            foreach ($results->hits as $hit) {
                $tags[] = new Tag(name: $hit['name'], slug: $hit['slug']);
            }

            $results = new SearchResults(hits: $tags, total: $results->total);

            return new ElasticSearchPaginator($results, $limit, $offset);
        }

        try {
            $data = $this->index->get(IndexName::Tags->value, $uriVariables['slug'], 'slug');
            $hit = $data['_source'] ?? null;
        } catch (IndexException $e) {
            if (404 === $e->getCode()) {
                return null;
            }

            throw $e;
        }

        return is_null($hit) ? $hit : new Tag(name: $hit['name'], slug: $hit['slug']);
    }
}
