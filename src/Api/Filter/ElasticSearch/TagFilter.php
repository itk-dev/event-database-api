<?php

namespace App\Api\Filter\ElasticSearch;

use ApiPlatform\Elasticsearch\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Symfony\Component\TypeInfo\TypeIdentifier;

final class TagFilter extends AbstractFilter
{
    public function apply(array $clauseBody, string $resourceClass, ?Operation $operation = null, array $context = []): array
    {
        $properties = $this->getProperties($resourceClass);
        $terms = [];

        /** @var string $property */
        foreach ($properties as $property) {
            if (empty($context['filters'][$property])) {
                // If no value or empty value is set, skip it.
                continue;
            }
            $terms[$property] = explode(',', $context['filters'][$property]);
        }

        return empty($terms) ? $terms : ['terms' => $terms + ['boost' => 1.0]];
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
                'type' => TypeIdentifier::ARRAY->value,
                'required' => false,
                'description' => 'Filter based on given tags',
                'is_collection' => true,
                'openapi' => new Parameter(
                    name: $filterParameterName,
                    in: 'query',
                    allowEmptyValue: true,
                    style: 'deepObject',
                    explode: false,
                    allowReserved: false,
                ),
            ];
        }

        return $description;
    }
}
