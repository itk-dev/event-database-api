<?php

namespace App\Api\Filter\ElasticSearch;

use ApiPlatform\Elasticsearch\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use App\Model\DateFilterConfig;
use App\Model\DateLimit;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\TypeInfo\TypeIdentifier;

/**
 * DateRangeFilter allows for defining filters on datetime fields with operators e.g.
 * - startDate[gt]=2004-02-12T15:19:21+00:00
 * - startDate[between]=2004-02-12T15:19:21+00:00..2004-03-12T15:19:21+00:00.
 *
 * @see ApiPlatform\Doctrine\Orm\Filter\RangeFilter
 */
final class DateRangeFilter extends AbstractFilter
{
    /** @var DateFilterConfig[] */
    private array $config;

    public function __construct(
        protected PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory,
        PropertyMetadataFactoryInterface $propertyMetadataFactory,
        ResourceClassResolverInterface $resourceClassResolver,
        protected ?NameConverterInterface $nameConverter = null,
        protected ?array $properties = null,
        array $config = [],
    ) {
        parent::__construct($propertyNameCollectionFactory, $propertyMetadataFactory, $resourceClassResolver, $nameConverter, $properties);

        // Convert config to DateFilterConfig objects for better type safety.
        foreach ($config as $field => $values) {
            $this->config[$field] = new DateFilterConfig(
                $values['limit'],
                $values['throwOnInvalid']
            );
        }
    }

    public function apply(array $clauseBody, string $resourceClass, ?Operation $operation = null, array $context = []): array
    {
        $ranges = [];

        if (!$this->properties) {
            return $ranges;
        }

        foreach ($this->properties as $property => $value) {
            if (!empty($context['filters'][$property])) {
                $ranges[] = $this->getElasticSearchQueryRanges($property, $context['filters'][$property]);
            }
        }

        return isset($ranges[1]) ? $ranges : $ranges[0] ?? $ranges;
    }

    public function getDescription(string $resourceClass): array
    {
        if (!$this->properties) {
            return [];
        }

        $description = [];

        foreach ($this->properties as $property => $value) {
            $description += $this->getFilterDescription($property, $this->config[$value]->limit, true);
            $description += $this->getFilterDescription($property, DateLimit::between);
            $description += $this->getFilterDescription($property, DateLimit::gt);
            $description += $this->getFilterDescription($property, DateLimit::gte);
            $description += $this->getFilterDescription($property, DateLimit::lt);
            $description += $this->getFilterDescription($property, DateLimit::lte);
        }

        return $description;
    }

    private function getElasticSearchQueryRanges(string $property, string|array $filter): array
    {
        if (null === $this->properties) {
            throw new \InvalidArgumentException('The property must be defined in the filter.');
        }
        if (!\is_array($filter)) {
            $fallbackOperator = $this->properties[$property];
            $operator = $this->config[$fallbackOperator]->limit;
            $value = $filter;
        } else {
            $operator = DateLimit::{array_key_first($filter)};
            $value = array_shift($filter);
        }

        switch ($operator) {
            case DateLimit::between:
                $values = explode('..', $value);

                if (2 !== count($values)) {
                    throw new \InvalidArgumentException('Invalid date range');
                }

                return [
                    'range' => [
                        $property => [
                            DateLimit::gt->name => $values[0],
                            DateLimit::lt->name => $values[1],
                        ],
                    ],
                ];
            case DateLimit::gt:
            case DateLimit::gte:
            case DateLimit::lt:
            case DateLimit::lte:
                return [
                    'range' => [
                        $property => [
                            $operator->name => $value,
                        ],
                    ],
                ];
            default:
                return [];
        }
    }

    private function getFilterDescription(string $fieldName, DateLimit $operator, bool $isDefault = false): array
    {
        $propertyName = $this->normalizePropertyName($fieldName);
        $key = $this->getFilterDescriptionKey($propertyName, $operator, $isDefault);

        return [
            $key => [
                'property' => $propertyName,
                'type' => TypeIdentifier::STRING->value,
                'required' => false,
                'description' => $this->getFilterDescriptionBody($propertyName, $operator, $isDefault),
            ],
        ];
    }

    private function getFilterDescriptionKey(string $propertyName, DateLimit $operator, bool $isDefault = false): string
    {
        return $isDefault ? $propertyName : $propertyName.'['.$operator->name.']';
    }

    private function getFilterDescriptionBody(string $propertyName, DateLimit $operator, bool $isDeprecated = false): string
    {
        $deprecatedBody = $isDeprecated ? sprintf(' (DEPRECATED - please use a filter with an explicit operator, e.g. %s[gt]=2004-02-12T15:19:21+00:00) ', $propertyName) : '';

        return match ($operator) {
            DateLimit::between => sprintf('Filter based on %s %s two ISO 8601 datetime (yyyy-MM-dd\'T\'HH:mm:ssz) seperated by \'..\', e.g. "2004-02-12T15:19:21+00:00..2004-02-13T16:20:22+00:00"', $propertyName, $operator->value),
            default => sprintf('Filter based on %s %s ISO 8601 datetime (yyyy-MM-dd\'T\'HH:mm:ssz), e.g. "2004-02-12T15:19:21+00:00"%s', $propertyName, $operator->value, $deprecatedBody),
        };
    }

    private function normalizePropertyName(string $property): string
    {
        if (!$this->nameConverter instanceof NameConverterInterface) {
            return $property;
        }

        return implode('.', array_map($this->nameConverter->normalize(...), explode('.', $property)));
    }
}
