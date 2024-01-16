<?php

namespace App\Api\Filter\ElasticSearch;

use ApiPlatform\Elasticsearch\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use Symfony\Component\PropertyInfo\Type;

/**
 * This class represents a filter that performs a search based on matching properties in a given resource.
 */
final class MatchFilter extends AbstractFilter
{
    public function apply(array $clauseBody, string $resourceClass, Operation $operation = null, array $context = []): array
    {
        $properties = $this->getProperties($resourceClass);
        $matches = [];

        /** @var string $property */
        foreach ($properties as $property) {
            if (!empty($context['filters'][$property])) {
                $matches[] = ['match' => [$property => $context['filters'][$property]]];
            }
        }

        return isset($matches[1]) ? $matches : $matches[0] ?? $matches;
    }

    public function getDescription(string $resourceClass): array
    {
        if (!$this->properties) {
            return [];
        }

        $description = [];
        foreach ($this->properties as $filterParameterName => $value) {
            $description[$filterParameterName] = [
                'property' => $filterParameterName,
                'type' => Type::BUILTIN_TYPE_STRING,
                'required' => false,
                'description' => 'Search field based on value given',
                'openapi' => [
                    'allowReserved' => false,
                    'allowEmptyValue' => true,
                    'explode' => false,
                ],
            ];
        }

        return $description;
    }
}
