<?php

namespace App\Model;

/**
 * Represents the different limits used for comparing dates.
 */
enum DateLimits: string
{
    case GreaterThan = 'grater than';
    case GreaterThanOrEqual = 'greater then or equal to';
    case LessThan = 'less then';
    case LessThanOrEqual = 'less then or equal to';
}
