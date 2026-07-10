<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\ElasticSearch;

use App\Service\ElasticSearch\ElasticIndexException;
use PHPUnit\Framework\TestCase;

/**
 * Pins how ElasticSearch client errors are turned into human-readable messages.
 * A 400 is parsed from the ES error JSON into "Type: reason"; anything else
 * collapses to a generic "Bad Request".
 */
final class ElasticIndexExceptionTest extends TestCase
{
    // Goal: a 400 parse error is rendered as a readable "Parse exception: <reason>".
    public function test400ParsesElasticErrorMessage(): void
    {
        $raw = '400 Bad Request: {"error":{"root_cause":[{"type":"parse_exception",'
            .'"reason":"failed to parse date field [2004-02-12T15:19:21+0000]: [details]"}]}}';

        $exception = new ElasticIndexException($raw, 400);

        $this->assertSame('Parse exception: failed to parse date field [2004-02-12T15:19:21+0000]', $exception->getMessage());
        $this->assertSame(400, $exception->getCode());
    }

    // Goal: non-400 codes collapse to a generic message (no ES JSON to parse).
    public function testNon400CollapsesToBadRequest(): void
    {
        $exception = new ElasticIndexException('500 Internal Server Error: something', 500);

        $this->assertSame('Bad Request', $exception->getMessage());
    }
}
