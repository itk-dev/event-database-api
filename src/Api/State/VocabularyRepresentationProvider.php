<?php

namespace App\Api\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Api\Dto\Vocabulary;
use App\Exception\IndexException;
use App\Model\IndexNames;
use App\Model\SearchResults;
use App\Service\ElasticSearch\ElasticSearchPaginator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

final class VocabularyRepresentationProvider extends AbstractProvider implements ProviderInterface
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
            $limit = $this->getImagesPerPage($context);
            $results = $this->index->getAll(IndexNames::Vocabularies->value, $filters, $offset, $limit);

            $vocabularies = [];
            foreach ($results->hits as $hit) {
                $vocabularies[] = new Vocabulary(name: $hit['name'], description: $hit['description'], tags: $hit['tags']);
            }

            $results = new SearchResults(hits: $vocabularies, total: $results->total);

            return new ElasticSearchPaginator($results, $limit, $offset);
        }

        try {
            $hit = $this->index->get(IndexNames::Vocabularies->value, $uriVariables['name'], 'name')['_source'];
        } catch (IndexException $e) {
            if (404 === $e->getCode()) {
                return null;
            }

            throw $e;
        }

        return new Vocabulary(name: $hit['name'], description: $hit['description'], tags: $hit['tags']);
    }
}
