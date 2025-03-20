<?php

namespace App\Model;

/**
 * Represents the different limits used for comparing dates.
 */
enum DateLimit: string
{
    case between = 'between';
    case gt = 'greater than';
    case gte = 'greater then or equal to';
    case lt = 'less then';
    case lte = 'less then or equal to';
}
