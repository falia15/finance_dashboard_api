<?php

namespace App\ApiResource;

/** One month of ProfileFixedExpenseYear. */
final class ProfileFixedExpenseMonth
{
    public function __construct(
        /** 1 to 12 */
        public readonly int $month,
        public readonly string $individual,
        public readonly string $householdShare,
        /** Individual amounts + household shares of the month */
        public readonly string $total,
    ) {
    }
}
