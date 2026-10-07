<?php

namespace App\Api\Filter\ElasticSearch;

use ApiPlatform\Elasticsearch\Filter\AbstractFilter;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
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

        if (null === $this->properties || [] === $this->properties) {
            return $ranges;
        }

        foreach (array_keys($this->properties) as $property) {
            if (isset($context['filters'][$property]) && '' !== $context['filters'][$property] && [] !== $context['filters'][$property]) {
                $ranges[] = $this->getElasticSearchQueryRanges($property, $context['filters'][$property]);
            }
        }

        return isset($ranges[1]) ? $ranges : $ranges[0] ?? $ranges;
    }

    public function getDescription(string $resourceClass): array
    {
        if (null === $this->properties || [] === $this->properties) {
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

        $throwOnInvalid = $this->config[$this->properties[$property]]->throwOnInvalid;

        if (!\is_array($filter)) {
            $operator = $this->config[$this->properties[$property]]->limit;
            $value = $filter;
        } else {
            $operator = $this->resolveOperator((string) array_key_first($filter), $throwOnInvalid);
            if (!$operator instanceof DateLimit) {
                return [];
            }
            $value = array_shift($filter);
        }

        switch ($operator) {
            case DateLimit::between:
                $values = explode('..', (string) $value);

                if (2 !== count($values)) {
                    if ($throwOnInvalid) {
                        throw new InvalidArgumentException(sprintf('Invalid date range for "%s": expected two ISO 8601 datetimes separated by "..".', $property));
                    }

                    return [];
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

    /**
     * Resolve a client-supplied operator key (e.g. "gt") to a DateLimit case.
     *
     * DateLimit case names are the operators the client uses in `field[op]=…`.
     * Returns null for an unknown key when the filter is not configured to
     * throw, so the caller can skip the clause instead of leaking a 5xx.
     */
    private function resolveOperator(string $key, bool $throwOnInvalid): ?DateLimit
    {
        foreach (DateLimit::cases() as $case) {
            if ($case->name === $key) {
                return $case;
            }
        }

        if ($throwOnInvalid) {
            throw new InvalidArgumentException(sprintf('Unknown date range operator "%s".', $key));
        }

        return null;
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
            DateLimit::between => sprintf('Filter based on %s %s two ISO 8601 datetime [(yyyy-MM-dd\'T\'HH:mm:ssz)](https://docs.oracle.com/en/java/javase/21/docs/api/java.base/java/time/format/DateTimeFormatter.html#patterns) seperated by \'..\', e.g. "2004-02-12T15:19:21+00:00..2004-02-13T16:20:22+00:00"', $propertyName, $operator->value),
            default => sprintf('Filter based on %s %s ISO 8601 datetime [(yyyy-MM-dd\'T\'HH:mm:ssz)](https://docs.oracle.com/en/java/javase/21/docs/api/java.base/java/time/format/DateTimeFormatter.html#patterns), e.g. "2004-02-12T15:19:21+00:00"%s', $propertyName, $operator->value, $deprecatedBody),
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
