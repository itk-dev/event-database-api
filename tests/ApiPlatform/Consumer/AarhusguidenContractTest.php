<?php

namespace App\Tests\ApiPlatform\Consumer;

use App\Tests\ApiPlatform\AbstractApiTestCase;

/**
 * Consumer contract for https://aarhusguiden.dk — the primary API consumer.
 *
 * These mirror the exact request shapes the site issues (verified from its live
 * network traffic against api.detskeriaarhus.dk): everything runs through the
 * `daily_occurrences` collection with combinations of `event.tags` (comma =
 * match-any), `event.title`, `event.publicAccess`, `start`/`end` and
 * pagination, plus the `events/{id}` item endpoint. If any of these break, the
 * site breaks — so this suite guards them as one explicit consumer contract,
 * independent of the per-filter unit/behavioural tests.
 *
 * Fixture daily occurrences: 10 → event 8 (tags aros/Koncert), 11 → event 8,
 * 12 → event 7 (tags aros/theoceanraceaarhus/ITKDev); all embedded events are
 * public (see tests/resources/daily_occurrences.json).
 */
class AarhusguidenContractTest extends AbstractApiTestCase
{
    protected static string $requestPath = '/api/v2/daily_occurrences';

    // Calendar / tag view: public events carrying a tag, within a date window,
    // page 1 of 6 — the site's headline request.
    public function testCalendarViewFiltersByTagPublicAccessAndDateWindow(): void
    {
        $response = $this->get([
            'event.tags' => 'ITKDev',
            'event.publicAccess' => 'true',
            'start' => static::formatDateTime('2024-01-01'),
            'end' => static::formatDateTime('2024-12-31'),
            'page' => 1,
            'itemsPerPage' => 6,
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertMemberIds([12], $response, 'entityId', message: 'Only daily occurrence 12 belongs to the ITKDev-tagged public event in 2024');
    }

    // Tag view: a category expands to several comma-separated tags, matched as OR.
    public function testTagViewAcceptsMultipleCommaSeparatedTags(): void
    {
        $response = $this->get([
            'event.tags' => 'ITKDev,Koncert',
            'event.publicAccess' => 'true',
        ]);

        $this->assertResponseIsSuccessful();
        // ITKDev → 12, Koncert → 10 & 11 (event 8). Comma is match-any.
        $this->assertMemberIds([10, 11, 12], $response, 'entityId', message: 'Comma-separated tags match any (ES terms query)');
    }

    // Free-text search box → event.title (tokenised text match).
    public function testSearchByEventTitle(): void
    {
        $response = $this->get(['event.title' => 'ITKDev', 'itemsPerPage' => 6]);

        $this->assertResponseIsSuccessful();
        $this->assertMemberIds([10, 11, 12], $response, 'entityId', message: 'All fixture events carry the "ITKDev" title token');
    }

    // Pagination: the site requests fixed-size pages and reads hydra:view to page.
    public function testPaginationExposesSliceAndNavigation(): void
    {
        $data = $this->get(['itemsPerPage' => 1, 'page' => 1])->toArray();

        $this->assertCount(1, $data['hydra:member'], 'itemsPerPage=1 must return a single member');
        $this->assertSame(3, $data['hydra:totalItems'], 'totalItems must report the full unpaginated count');
        $this->assertArrayHasKey('hydra:view', $data, 'A multi-page result must expose hydra:view for navigation');
        $this->assertArrayHasKey('hydra:next', $data['hydra:view'], 'Page 1 of 3 must expose hydra:next');
    }

    // The site reads these fields off each daily-occurrence member and its
    // embedded event; losing any of them breaks rendering.
    public function testMembersCarryTheFieldsTheSiteRenders(): void
    {
        $data = $this->get(['itemsPerPage' => 1])->toArray();
        $member = $data['hydra:member'][0];

        foreach (['entityId', 'start', 'end', 'event'] as $key) {
            $this->assertArrayHasKey($key, $member, 'daily occurrence member must expose '.$key);
        }
        foreach (['entityId', 'title', 'tags', 'imageUrls', 'location', 'url'] as $key) {
            $this->assertArrayHasKey($key, $member['event'], 'embedded event must expose '.$key);
        }
    }

    // Event detail page: GET /events/{id} returns the D6 collection wrapper and
    // the site reads hydra:member[0]. Pinning this makes any future switch to a
    // plain single-item response a conscious, consumer-breaking decision.
    public function testEventDetailReturnsCollectionWrapper(): void
    {
        $data = $this->get([], '/api/v2/events/7')->toArray();

        $this->assertArrayHasKey('hydra:member', $data, 'Item endpoint must return a hydra:Collection wrapper (D6)');
        $this->assertNotEmpty($data['hydra:member'], 'Wrapper must contain the single event as member[0]');
        $this->assertSame(7, $data['hydra:member'][0]['entityId'], 'member[0] must be the requested event');
        foreach (['title', 'tags', 'imageUrls', 'location', 'occurrences'] as $key) {
            $this->assertArrayHasKey($key, $data['hydra:member'][0], 'detail event must expose '.$key);
        }
    }
}
