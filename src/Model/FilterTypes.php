<?php

namespace App\Model;

/**
 * Represents the types of filters that can be applied.
 */
enum FilterTypes: string
{
    public const Filters = 'filters';
    public const Sort = 'sort';
}
