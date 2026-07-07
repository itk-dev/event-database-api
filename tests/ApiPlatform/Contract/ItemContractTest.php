<?php

namespace App\Tests\ApiPlatform\Contract;

use App\Tests\ApiPlatform\AbstractApiTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Locks the item ("GET one") response shape, which is deliberately inconsistent
 * in the current API and therefore a high BC-break risk on upgrade:
 *
 *  - Event / Occurrence / DailyOccurrence / Location / Organization item
 *    endpoints return a hydra:Collection wrapper of exactly ONE member (NOT a
 *    single JSON-LD item), and the member has no @id/@var.
 *  - Tag / Vocabulary item endpoints return a flat single item with @id/@var.
 *
 * See PR notes: this asymmetry is current behaviour, not necessarily desired —
 * the tests exist to catch it changing during the upgrade.
 */
class ItemContractTest extends AbstractApiTestCase
{
    protected static string $requestPath = '/api/v2/events';

    #[DataProvider('collectionWrappedItemProvider')]
    public function testCollectionWrappedItem(string $path, int $entityId): void
    {
        $data = $this->get([], $path)->toArray();

        $this->assertSame($path, $data['@id'], $path.' @id');
        $this->assertSame('hydra:Collection', $data['@type'], $path.' item is currently wrapped in a hydra:Collection');
        $this->assertSame(1, $data['hydra:totalItems'], $path.' should wrap exactly one item');
        $this->assertCount(1, $data['hydra:member']);
        $this->assertSame($entityId, $data['hydra:member'][0]['entityId']);
        $this->assertArrayNotHasKey('@id', $data['hydra:member'][0], 'Wrapped member currently has no @id');
        $this->assertArrayNotHasKey('@type', $data['hydra:member'][0], 'Wrapped member currently has no @type');
    }

    #[DataProvider('flatItemProvider')]
    public function testFlatItem(string $path, string $type, string $slug): void
    {
        $data = $this->get([], $path)->toArray();

        $this->assertSame('/api/v2/contexts/'.$type, $data['@context']);
        $this->assertSame($path, $data['@id'], $path.' @id');
        $this->assertSame($type, $data['@type'], $path.' flat item @type');
        $this->assertSame($slug, $data['slug']);
        $this->assertArrayNotHasKey('hydra:member', $data, $path.' is a flat item, not a collection');
    }

    public static function collectionWrappedItemProvider(): iterable
    {
        yield 'events' => ['/api/v2/events/7', 7];
        yield 'occurrences' => ['/api/v2/occurrences/10', 10];
        yield 'daily_occurrences' => ['/api/v2/daily_occurrences/10', 10];
        yield 'locations' => ['/api/v2/locations/4', 4];
        yield 'organizations' => ['/api/v2/organizations/9', 9];
    }

    public static function flatItemProvider(): iterable
    {
        yield 'tags' => ['/api/v2/tags/aros', 'Tag', 'aros'];
        yield 'vocabularies' => ['/api/v2/vocabularies/aarhusguiden', 'Vocabulary', 'aarhusguiden'];
    }
}
