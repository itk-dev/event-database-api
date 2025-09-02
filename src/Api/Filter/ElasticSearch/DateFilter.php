<?php

namespace App\Api\Filter\ElasticSearch;

use ApiPlatform\Elasticsearch\Filter\AbstractFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\OpenApi\Model\Parameter;
use App\Model\DateFilterConfig;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\TypeInfo\TypeIdentifier;

/**
 * This class represents a filter that performs a search based on matching properties in a given resource.
 *
 * @deprecated please us DataRangeFilter
 */
final class DateFilter extends AbstractFilter
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
                $conf = $this->config[$value];
                $ranges[] = [
                    'range' => [
                        $property => [
                            $conf->getCompareOperator($conf->limit) => $context['filters'][$property],
                        ],
                    ],
                ];
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
        foreach ($this->properties as $filterParameterName => $value) {
            $description[$filterParameterName] = [
                'property' => $filterParameterName,
                'type' => TypeIdentifier::STRING->value,
                'required' => false,
                'description' => 'Filter base on ISO 8601 datetime (yyyy-MM-dd\'T\'HH:mm:ssz), e.g. "2004-02-12T15:19:21+00:00" ('.$this->config[$value]->limit->value.')',
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
