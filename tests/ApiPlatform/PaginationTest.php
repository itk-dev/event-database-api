<?php

namespace App\Tests\ApiPlatform;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Verify the pagination contract: itemsPerPage client override, max clamp,
 * default counts, and hydra:view navigation links.
 */
class PaginationTest extends AbstractApiTestCase
{
    protected static string $requestPath = '/api/v2/events';

    #[DataProvider('resourceProvider')]
    public function testCollectionExposesHydraKeys(string $path, int $fixtureCount, int $maxPerPage): void
    {
        unset($maxPerPage);

        $response = $this->get([], $path);
        $data = $response->toArray();

        $this->assertArrayHasKey('hydra:member', $data);
        $this->assertArrayHasKey('hydra:totalItems', $data);
        $this->assertSame($fixtureCount, $data['hydra:totalItems'], $path.' should report '.$fixtureCount.' total items');
    }

    #[DataProvider('resourceProvider')]
    public function testItemsPerPageClientOverride(string $path, int $fixtureCount, int $maxPerPage): void
    {
        unset($maxPerPage);

        if ($fixtureCount < 2) {
            $this->markTestSkipped($path.' has < 2 fixtures — cannot test itemsPerPage=1 slicing');
        }

        $response = $this->get(['itemsPerPage' => 1], $path);
        $data = $response->toArray();

        $this->assertCount(1, $data['hydra:member'], $path.' should honour itemsPerPage=1');
        $this->assertSame($fixtureCount, $data['hydra:totalItems']);
    }

    #[DataProvider('resourceProvider')]
    public function testItemsPerPageRespectsMaximum(string $path, int $fixtureCount, int $maxPerPage): void
    {
        $response = $this->get(['itemsPerPage' => $maxPerPage + 100], $path);
        $data = $response->toArray();

        $expected = min($fixtureCount, $maxPerPage);
        $this->assertLessThanOrEqual($maxPerPage, count($data['hydra:member']), $path.' must clamp itemsPerPage to '.$maxPerPage);
        $this->assertCount($expected, $data['hydra:member']);
    }

    public function testPageNavigationLinksPresentWhenSliced(): void
    {
        $response = $this->get(['itemsPerPage' => 1, 'page' => 1], '/api/v2/events');
        $data = $response->toArray();

        $this->assertArrayHasKey('hydra:view', $data, 'Multi-page result must expose hydra:view');
        $view = $data['hydra:view'];

        $this->assertArrayHasKey('hydra:next', $view, 'Page 1 of 3 should expose hydra:next');
        $this->assertArrayHasKey('hydra:last', $view);

        $response = $this->get(['itemsPerPage' => 1, 'page' => 3], '/api/v2/events');
        $data = $response->toArray();
        $this->assertArrayHasKey('hydra:view', $data);
        $this->assertArrayHasKey('hydra:previous', $data['hydra:view'], 'Page 3 of 3 should expose hydra:previous');
    }

    public static function resourceProvider(): iterable
    {
        // [path, fixture count, paginationMaximumItemsPerPage]
        yield 'events' => ['/api/v2/events', 3, 50];
        yield 'occurrences' => ['/api/v2/occurrences', 3, 50];
        yield 'daily_occurrences' => ['/api/v2/daily_occurrences', 3, 50];
        yield 'locations' => ['/api/v2/locations', 2, 100];
        yield 'organizations' => ['/api/v2/organizations', 3, 100];
        yield 'tags' => ['/api/v2/tags', 5, 100];
        yield 'vocabularies' => ['/api/v2/vocabularies', 2, 100];
    }
}
