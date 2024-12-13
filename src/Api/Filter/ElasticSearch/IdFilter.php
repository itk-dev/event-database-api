<?php

namespace App\Api\Filter\ElasticSearch;

use ApiPlatform\Elasticsearch\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use Symfony\Component\PropertyInfo\Type;

final class IdFilter extends AbstractFilter
{
    public function apply(array $clauseBody, string $resourceClass, ?Operation $operation = null, array $context = []): array
    {
        $properties = $this->getProperties($resourceClass);
        $result = [];

        /** @var string $property */
        foreach ($properties as $property) {
            if (empty($context['filters'][$property])) {
                // If no value or empty value is set, skip it.
                continue;
            }
            $terms = [];
            $terms[$property] = explode(',', $context['filters'][$property]);
            $terms['boost'] = 1.0;
            $result[]['terms'] = $terms;
        }

        return $result;
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
                'type' => Type::BUILTIN_TYPE_ARRAY,
                'required' => false,
                'description' => 'Filter based on given entity ids',
                'is_collection' => true,
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
