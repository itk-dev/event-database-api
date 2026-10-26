<?php

declare(strict_types=1);

namespace App\Api\Filter\ElasticSearch;

use ApiPlatform\Elasticsearch\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Symfony\Component\TypeInfo\TypeIdentifier;

final class IdFilter extends AbstractFilter
{
    public function apply(array $clauseBody, string $resourceClass, ?Operation $operation = null, array $context = []): array
    {
        $properties = $this->getProperties($resourceClass);
        $result = [];

        /** @var string $property */
        foreach ($properties as $property) {
            if (!isset($context['filters'][$property])) {
                // If no value or empty value is set, skip it.
                continue;
            }
            if ('' === $context['filters'][$property]) {
                // If no value or empty value is set, skip it.
                continue;
            }
            if ([] === $context['filters'][$property]) {
                // If no value or empty value is set, skip it.
                continue;
            }
            $terms = [];
            $terms[$property] = explode(',', (string) $context['filters'][$property]);
            $terms['boost'] = 1.0;
            $result[]['terms'] = $terms;
        }

        return $result;
    }

    public function getDescription(string $resourceClass): array
    {
        if (null === $this->properties || [] === $this->properties) {
            return [];
        }

        $description = [];
        foreach (array_keys($this->properties) as $filterParameterName) {
            $description[$filterParameterName] = [
                'property' => $filterParameterName,
                'type' => TypeIdentifier::ARRAY->value,
                'required' => false,
                'description' => 'Filter based on given entity ids',
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
