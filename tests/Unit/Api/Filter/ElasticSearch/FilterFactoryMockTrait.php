<?php

namespace App\Tests\Unit\Api\Filter\ElasticSearch;

use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use PHPUnit\Framework\MockObject\Stub;

/**
 * The custom Elasticsearch filters extend API Platform's AbstractFilter, whose
 * constructor requires three metadata factories. When a filter is configured
 * with an explicit `properties` map (as every resource here does),
 * `AbstractFilter::getProperties()` yields `array_keys($properties)` and never
 * touches the factories — so inert test stubs are enough to unit-test
 * `apply()`/`getDescription()` without booting the kernel or Elasticsearch.
 */
trait FilterFactoryMockTrait
{
    /**
     * @return array{PropertyNameCollectionFactoryInterface&Stub, PropertyMetadataFactoryInterface&Stub, ResourceClassResolverInterface&Stub}
     */
    private function filterDependencies(): array
    {
        // Stubs (not mocks) — these collaborators are never called, so they need
        // no expectations.
        return [
            $this->createStub(PropertyNameCollectionFactoryInterface::class),
            $this->createStub(PropertyMetadataFactoryInterface::class),
            $this->createStub(ResourceClassResolverInterface::class),
        ];
    }
}
