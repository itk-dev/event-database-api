<?php

namespace App\Tests\ApiPlatform;

/**
 * Pin the default result ordering — the contract of
 * ElasticSearchIndex::getSort(), which is otherwise asserted nowhere. There is
 * no client-facing sort parameter yet, so this order IS the contract.
 *
 * Covers all three non-default branches:
 *  - Events               → _score, then title.keyword ascending
 *  - Occurrences / Daily  → start ascending
 *  - Tags/Vocab/Loc/Org   → _score, then name.keyword ascending
 */
class SortTest extends AbstractApiTestCase
{
    protected static string $requestPath = '/api/v2/events';

    public function testEventsAreSortedByTitleAscending(): void
    {
        $data = $this->get([], '/api/v2/events')->toArray();
        $titles = array_column($data['hydra:member'], 'title');

        $sorted = $titles;
        sort($sorted, SORT_STRING);

        self::assertSame($sorted, $titles, 'Events must be ordered by title.keyword ascending');
    }

    public function testOccurrencesAreSortedByStartAscending(): void
    {
        $response = $this->get([], '/api/v2/occurrences');

        // Fixture starts: 11 = 2024-11-08, 10 = 2024-12-07, 12 = 2024-12-08.
        $this->assertMemberIds([11, 10, 12], $response, 'entityId', ordered: true, message: 'Occurrences must be ordered by start ascending');

        $starts = array_column($response->toArray()['hydra:member'], 'start');
        $sorted = $starts;
        sort($sorted, SORT_STRING);
        self::assertSame($sorted, $starts, 'start values must be ascending');
    }

    public function testDailyOccurrencesAreSortedByStartAscending(): void
    {
        $response = $this->get([], '/api/v2/daily_occurrences');

        $this->assertMemberIds([11, 10, 12], $response, 'entityId', ordered: true, message: 'Daily occurrences must be ordered by start ascending');
    }

    public function testOrganizationsAreSortedByNameAscending(): void
    {
        $response = $this->get([], '/api/v2/organizations');

        // name.keyword ascending (byte order): Aakb (10), Dokk1 (11), ITKDev (9).
        $this->assertMemberIds([10, 11, 9], $response, 'entityId', ordered: true, message: 'Organizations must be ordered by name.keyword ascending');
    }
}
