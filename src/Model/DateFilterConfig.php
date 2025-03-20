<?php

namespace App\Model;

readonly class DateFilterConfig
{
    public function __construct(
        public DateLimit $limit,
        public bool $throwOnInvalid,
    ) {
    }

    /**
     * Returns the comparison operator based on the given DateLimits.
     *
     * @param DateLimit $dateLimit
     *   The DateLimit Enum to determine the comparison operator
     *
     * @return string
     *   The comparison operator
     */
    public function getCompareOperator(DateLimit $dateLimit): string
    {
        return $dateLimit->name;
    }
}
