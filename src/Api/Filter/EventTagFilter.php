<?php

namespace App\Api\Filter;

use ApiPlatform\Elasticsearch\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use Symfony\Component\PropertyInfo\Type;

final class EventTagFilter extends AbstractFilter
{
    public function apply(array $clauseBody, string $resourceClass, Operation $operation = null, array $context = []): array
    {
        $properties = $this->getProperties($resourceClass);
        $terms = [
            'boost' => 1.0,
        ];
        foreach ($properties as $property) {
            if (empty($context['filters'][$property])) {
                continue;
            }
            $terms[$property] = $context['filters'][$property];
        }

        return ['terms' => $terms];
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
                'description' => 'Filter base on values given',
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
