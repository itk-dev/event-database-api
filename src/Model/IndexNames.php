<?php

namespace App\Model;

/**
 * Represents an enumeration of index names.
 */
enum IndexNames: string
{
    case Events = 'events';
    case Organization = 'organization';

    public static function values(): array
    {
        return array_column(IndexNames::cases(), 'value');
    }
}
