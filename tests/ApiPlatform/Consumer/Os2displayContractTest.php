<?php

declare(strict_types=1);

namespace App\Tests\ApiPlatform\Consumer;

use App\Tests\ApiPlatform\AbstractApiTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;

/**
 * Consumer contract for OS2display (os2display/display-api-service) — its
 * `EventDatabaseApiV2FeedType` + `EventDatabaseApiV2Helper` fetch posters and
 * config options from this API server-side. (Its React admin/client apps do not
 * call this API directly; they consume the transformed poster output.).
 *
 * Mirrors the exact request shapes the helper issues:
 *  - `occurrences` collection: IdFilter (event.organizer/location.entityId),
 *    TagFilter (event.tags), DateRangeFilter `end[gt]`, pagination.
 *  - `occurrences/{id}` item (D6 wrapper → member[0]) for a single poster.
 *  - `events` search: MatchFilter (title), IdFilter, `occurrences.end[gt]`.
 *  - `tags`/`organizations`/`locations` collections with `hydra:totalItems`
 *    driving the paging loop.
 *  - item 404 → OS2display unpublishes the slide off the HTTP status alone
 *    (it never reads the error body, so the hydra error-field naming is moot).
 *
 * Fixtures: occurrences 10/11 → event 8 (tags aros/Koncert), 12 → event 7
 * (tags …/ITKDev); all occurrences organizer 9, location 4. Events 7 & 8 are at
 * location 4, event 9 at location 5.
 */
final class Os2displayContractTest extends AbstractApiTestCase
{
    protected static string $requestPath = '/api/v2/occurrences';

    // Subscription poster: upcoming occurrences (end[gt]) for a tag/location.
    public function testSubscriptionOccurrencesQuery(): void
    {
        $response = $this->get([
            'event.tags' => 'Koncert',
            'event.location.entityId' => 4,
            'end' => ['gt' => self::formatDateTime('2024-12-01')],
            'itemsPerPage' => 20,
            'page' => 1,
        ]);

        $this->assertResponseIsSuccessful();
        // Koncert → occ 10 & 11 (event 8); end[gt] 2024-12-01 keeps only occ 10 (11 ends 2024-11-08).
        $this->assertMemberIds([10], $response, 'entityId', message: 'end[gt] must exclude the November occurrence');
    }

    // IdFilter accepts comma-separated ids (helper joins organizer/location ids).
    public function testOccurrencesAcceptCommaSeparatedEntityIds(): void
    {
        $response = $this->get(['event.organizer.entityId' => '9,999']);

        $this->assertResponseIsSuccessful();
        $this->assertMemberIds([10, 11, 12], $response, 'entityId', message: 'All fixture occurrences belong to organizer 9');
    }

    // Single poster: GET /occurrences/{id} returns the D6 collection wrapper.
    public function testSingleOccurrenceReturnsCollectionWrapper(): void
    {
        $data = $this->get([], '/api/v2/occurrences/10')->toArray();

        $this->assertArrayHasKey('hydra:member', $data, 'occurrences item endpoint must return a hydra:Collection wrapper (D6)');
        $this->assertNotEmpty($data['hydra:member']);
        $member = $data['hydra:member'][0];
        $this->assertSame(10, $member['entityId']);
        // Fields the helper reads to build a Poster from an occurrence.
        foreach (['start', 'end', 'ticketPriceRange', 'status', 'event'] as $key) {
            $this->assertArrayHasKey($key, $member, 'occurrence must expose '.$key);
        }
        foreach (['entityId', 'title', 'excerpt', 'description', 'url', 'ticketUrl', 'imageUrls', 'location', 'organizer'] as $key) {
            $this->assertArrayHasKey($key, $member['event'], 'occurrence.event must expose '.$key);
        }
    }

    // Search (events): title + location filter + upcoming occurrences.
    public function testEventSearchQuery(): void
    {
        $response = $this->get([
            'title' => 'ITKDev',
            'location.entityId' => 4,
            'occurrences.end' => ['gt' => self::formatDateTime('2024-01-01')],
            'itemsPerPage' => 10,
        ], '/api/v2/events');

        $this->assertResponseIsSuccessful();
        // All events match the title token; location 4 keeps 7 & 8 (event 9 is at location 5).
        $this->assertMemberIds([7, 8], $response, 'entityId', message: 'location.entityId must exclude the event at location 5');
    }

    // Entity config: GET /events/{id} (D6 wrapper) with the fields mapEventToOutput reads.
    public function testEventEntityReturnsCollectionWrapper(): void
    {
        $data = $this->get([], '/api/v2/events/7')->toArray();

        $this->assertNotEmpty($data['hydra:member'] ?? [], 'events item endpoint must return a non-empty hydra:Collection wrapper (D6)');
        $event = $data['hydra:member'][0];
        $this->assertSame(7, $event['entityId']);
        foreach (['title', 'organizer', 'location', 'imageUrls', 'occurrences'] as $key) {
            $this->assertArrayHasKey($key, $event, 'event must expose '.$key);
        }
        $this->assertArrayHasKey('name', $event['organizer'], 'organizer.name is required');
        $this->assertArrayHasKey('name', $event['location'], 'location.name is required');
        foreach (['small', 'medium', 'large'] as $size) {
            $this->assertArrayHasKey($size, $event['imageUrls'], 'imageUrls.'.$size.' is required');
        }
    }

    // Options: paginated tags/organizations/locations, hydra:totalItems drives paging.
    #[DataProvider('optionEntityProvider')]
    public function testOptionCollectionsExposeTotalItemsAndIdField(string $path, string $idField): void
    {
        $data = $this->get(['itemsPerPage' => 50, 'page' => 1], $path)->toArray();

        $this->assertIsInt($data['hydra:totalItems'], $path.' hydra:totalItems must be an int (paging loop compares it)');
        $this->assertNotEmpty($data['hydra:member']);
        $this->assertArrayHasKey($idField, $data['hydra:member'][0], $path.' option must expose '.$idField);
        $this->assertArrayHasKey('name', $data['hydra:member'][0], $path.' option must expose name');
    }

    public static function optionEntityProvider(): iterable
    {
        // toPosterOption uses `name` as the id for tags, `entityId` for the rest.
        yield 'tags' => ['/api/v2/tags', 'name'];
        yield 'organizations' => ['/api/v2/organizations', 'entityId'];
        yield 'locations' => ['/api/v2/locations', 'entityId'];
    }

    // A removed/expired item 404s; OS2display unpublishes the slide off the
    // status code alone — it never reads the error body.
    public function testMissingItemReturnsNotFoundStatus(): void
    {
        $response = $this->get([], '/api/v2/occurrences/99999');

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
