<?php

namespace App\Api\Filter\ElasticSearch;

use ApiPlatform\Elasticsearch\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Symfony\Component\TypeInfo\TypeIdentifier;

final class BooleanFilter extends AbstractFilter
{
    public function apply(array $clauseBody, string $resourceClass, ?Operation $operation = null, array $context = []): array
    {
        $properties = $this->getProperties($resourceClass);
        $terms = [];

        /** @var string $property */
        foreach ($properties as $property) {
            if (!isset($context['filters'][$property]) || '' === $context['filters'][$property] || [] === $context['filters'][$property]) {
                // If no value or empty value is set, skip it.
                continue;
            }
            $terms[$property] = explode(',', $context['filters'][$property]);
        }

        return [] === $terms ? $terms : ['terms' => $terms + ['boost' => 1.0]];
    }

    public function getDescription(string $resourceClass): array
    {
        if (null === $this->properties || [] === $this->properties) {
            return [];
        }

        $description = [];
        foreach ($this->properties as $filterParameterName => $value) {
            $description[$filterParameterName] = [
                'property' => $filterParameterName,
                'type' => TypeIdentifier::BOOL->value,
                'required' => false,
                'description' => 'Is this a public event',
                'is_collection' => false,
                'openapi' => new Parameter(
                    name: $filterParameterName,
                    in: 'query',
                    allowEmptyValue: true,
                    explode: false,
                    allowReserved: false,
                ),
            ];
        }

        return $description;
    }
}
