<?php

namespace App\Api\Filter;

use ApiPlatform\Elasticsearch\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use Symfony\Component\PropertyInfo\Type;

final class StringFieldFilter extends AbstractFilter
{
    public function apply(array $clauseBody, string $resourceClass, Operation $operation = null, array $context = []): array
    {
        $properties = $this->getProperties($resourceClass);
        $terms = [];

        /** @var string $property */
        foreach ($properties as $property) {
            if (empty($context['filters'][$property])) {
                // If no value or empty value is set, skip it.
                continue;
            }

            $t = 1;
        }

        //        return empty($terms) ? $terms : ['terms' => $terms + ['boost' => 1.0]];
        return [];
    }

    public function getDescription(string $resourceClass): array
    {
        if (!$this->properties) {
            return [];
        }

        $description = [];
        foreach ($this->properties as $filterParameterName => $value) {
            // Look for which field this should be filtering on.
            if ('field' === $filterParameterName) {
                $description[$value] = [
                    'property' => $value,
                    'type' => Type::BUILTIN_TYPE_STRING,
                    'required' => false,
                    'description' => 'Search field based on value given',
                    'is_collection' => true,
                    'openapi' => [
                        'allowReserved' => false,
                        'allowEmptyValue' => true,
                        'explode' => false,
                    ],
                ];
            }
        }

        return $description;
    }
}
