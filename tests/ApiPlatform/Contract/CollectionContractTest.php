<?php

namespace App\Tests\ApiPlatform\Contract;

use App\Tests\ApiPlatform\AbstractApiTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Locks the JSON-LD / Hydra collection envelope and the exact member field set
 * of every resource, so an API Platform upgrade cannot silently change the
 * response contract (renamed/dropped fields, changed @var, altered hydra:*
 * envelope). These assertions are deliberately independent of API Platform's
 * self-generated JSON schema (which regenerates with the upgrade).
 *
 * Captured against the current version; treat any diff during the upgrade as a
 * potential BC break to reconcile against the "no BC changes" rule.
 */
class CollectionContractTest extends AbstractApiTestCase
{
    protected static string $requestPath = '/api/v2/events';

    #[DataProvider('collectionProvider')]
    public function testCollectionEnvelope(string $path, string $context, array $expectedKeys): void
    {
        unset($expectedKeys);
        $data = $this->get([], $path)->toArray();

        $this->assertSame('/api/v2/contexts/'.$context, $data['@context'], $path.' @context');
        $this->assertSame($path, $data['@id'], $path.' @id');
        $this->assertSame('hydra:Collection', $data['@type'], $path.' @type');
        $this->assertIsInt($data['hydra:totalItems'], $path.' hydra:totalItems must be an int');
        $this->assertIsList($data['hydra:member'], $path.' hydra:member must be a JSON list');
    }

    #[DataProvider('collectionProvider')]
    public function testCollectionMemberFieldSet(string $path, string $context, array $expectedKeys): void
    {
        unset($context);
        $data = $this->get([], $path)->toArray();
        $this->assertNotEmpty($data['hydra:member'], $path.' needs at least one fixture member');

        $this->assertEqualsCanonicalizing(
            $expectedKeys,
            array_keys($data['hydra:member'][0]),
            $path.' member field set changed — potential BC break'
        );
    }

    #[DataProvider('collectionProvider')]
    public function testCollectionExposesHydraSearch(string $path, string $context, array $expectedKeys): void
    {
        unset($context, $expectedKeys);
        $data = $this->get([], $path)->toArray();

        $this->assertArrayHasKey('hydra:search', $data, $path.' must expose hydra:search');
        $this->assertSame('hydra:IriTemplate', $data['hydra:search']['@type']);
        $this->assertIsString($data['hydra:search']['hydra:template']);
        $this->assertIsList($data['hydra:search']['hydra:mapping']);
        $this->assertSame('IriTemplateMapping', $data['hydra:search']['hydra:mapping'][0]['@type']);
    }

    public static function collectionProvider(): iterable
    {
        // Nested resources (Event/Occurrence/DailyOccurrence/Location/Organization)
        // are serialized as plain objects — NOTE their members carry NO @id/@type.
        yield 'events' => ['/api/v2/events', 'Event', [
            'entityId', 'title', 'excerpt', 'description', 'url', 'ticketUrl', 'publicAccess',
            'organizer', 'partners', 'occurrences', 'dailyOccurrences', 'tags', 'imageUrls',
            'created', 'updated', 'location',
        ]];
        yield 'occurrences' => ['/api/v2/occurrences', 'Occurrence', [
            'entityId', 'start', 'end', 'ticketPriceRange', 'room', 'event', 'status',
        ]];
        yield 'daily_occurrences' => ['/api/v2/daily_occurrences', 'DailyOccurrence', [
            'entityId', 'start', 'end', 'ticketPriceRange', 'room', 'status', 'event',
        ]];
        yield 'locations' => ['/api/v2/locations', 'Location', [
            'entityId', 'name', 'image', 'url', 'telephone', 'disabilityAccess', 'mail',
            'street', 'suite', 'region', 'city', 'country', 'postalCode', 'coordinates',
        ]];
        yield 'organizations' => ['/api/v2/organizations', 'Organization', [
            'entityId', 'name', 'email', 'url', 'created', 'updated',
        ]];
        // Tag/Vocabulary ARE true API Platform resources — their members DO carry @id/@type.
        yield 'tags' => ['/api/v2/tags', 'Tag', [
            '@id', '@type', 'slug', 'name',
        ]];
        yield 'vocabularies' => ['/api/v2/vocabularies', 'Vocabulary', [
            '@id', '@type', 'slug', 'name', 'description', 'tags',
        ]];
    }
}
