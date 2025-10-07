<?php

namespace App\Service\ElasticSearch;

use App\Exception\IndexException;
use Symfony\Component\String\UnicodeString;

class ElasticIndexException extends IndexException
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        $message = $this->parseElasticErrorMessage($message, $code);

        parent::__construct($message, $code, $previous);
    }

    private function parseElasticErrorMessage(string $message, int $code): string
    {
        return match ($code) {
            400 => $this->parse400($message),
            default => 'Bad Request',
        };
    }

    private function parse400(string $message): string
    {
        /*
        / Message has format:
        / 400 Bad Request: {\"error\":{\"root_cause\":[{\"type\":\"parse_exception\",
        / \"reason\":\"failed to parse date field [2004-02-12T15:19:21+0000] with format [yyyy-MM-dd'T'HH:mm:ssz]:
        / [failed to parse date field [2004-02-12T15:19:21+0000] with format [yyyy-MM-dd'T'HH:mm:ssz]]\"}],\"ty
        / ...
        */
        $message = str_replace('400 Bad Request: ', '', $message);
        $message = json_decode($message, false, 512, JSON_THROW_ON_ERROR);

        // Convert snake_case type to readable format (e.g., "parse_exception" -> "Parse exception")
        $type = (new UnicodeString($message->error->root_cause[0]->type))
            ->replace('_', ' ')
            ->title()
            ->toString();

        $reason = explode(': ', $message->error->root_cause[0]->reason)[0];

        return $type.': '.$reason;
    }
}
