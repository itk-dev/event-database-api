<?php

namespace App\Model;

class DateFilterConfig
{
    public function __construct(
        public readonly DateLimits $limit,
        public readonly bool $throwOnInvalid,
    ) {
    }

    /**
     * Returns the comparison operator based on the given DateLimits.
     *
     * @param DateLimits $dateLimit
     *   The DateLimit Enum to determine the comparison operator
     *
     * @return string
     *   The comparison operator
     */
    public function getCompareOperator(DateLimits $dateLimit): string
    {
        return match ($dateLimit) {
            DateLimits::GreaterThanOrEqual => 'gte',
            DateLimits::GreaterThan => 'gt',
            DateLimits::LessThan => 'lt',
            DateLimits::LessThanOrEqual => 'lte',
        };
    }
}
