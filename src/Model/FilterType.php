<?php

namespace App\Model;

/**
 * Represents the types of filters that can be applied.
 */
enum FilterType: string
{
    case Filters = 'filters';
    case Sort = 'sort';
}
