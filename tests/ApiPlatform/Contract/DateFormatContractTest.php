<?php

declare(strict_types=1);

namespace App\Tests\ApiPlatform\Contract;

use App\Tests\ApiPlatform\AbstractApiTestCase;

/**
 * Locks the datetime serialization format: ISO 8601 with an explicit timezone
 * offset (e.g. "2024-12-08T12:30:00+01:00"), rendered in the local timezone
 * (not UTC "Z"). Date serialization is a common BC-sensitive area on upgrade.
 */
final class DateFormatContractTest extends AbstractApiTestCase
{
    protected static string $requestPath = '/api/v2/events';

    private const string ISO_8601_OFFSET = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/';

    public function testOccurrenceDatesUseIso8601WithOffset(): void
    {
        $data = $this->get([], '/api/v2/occurrences')->toArray();
        $member = $data['hydra:member'][0];

        $this->assertMatchesRegularExpression(self::ISO_8601_OFFSET, $member['start'], 'occurrence.start');
        $this->assertMatchesRegularExpression(self::ISO_8601_OFFSET, $member['end'], 'occurrence.end');
    }

    public function testAuditDatesUseIso8601WithOffset(): void
    {
        $data = $this->get([], '/api/v2/organizations')->toArray();
        $member = $data['hydra:member'][0];

        $this->assertMatchesRegularExpression(self::ISO_8601_OFFSET, $member['created'], 'organization.created');
        $this->assertMatchesRegularExpression(self::ISO_8601_OFFSET, $member['updated'], 'organization.updated');
    }
}
