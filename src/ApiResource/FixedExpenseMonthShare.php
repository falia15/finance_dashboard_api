<?php

namespace App\ApiResource;

/**
 * A filled month of a household fixed expense line with the profile's
 * share of it, in HouseholdFixedExpenseLine::$amounts.
 */
final class FixedExpenseMonthShare
{
    public function __construct(
        /** FixedExpenseAmount id */
        public readonly int $id,
        /** 1 to 12 */
        public readonly int $month,
        public readonly string $amount,
        public readonly int $appliedDivisor,
        public readonly string $share,
    ) {
    }
}
