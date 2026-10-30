<?php

namespace App\ApiResource;

/** One month of HouseholdFixedExpenseGrid. */
final class HouseholdFixedExpenseMonth
{
    public function __construct(
        /** 1 to 12 */
        public readonly int $month,
        /** Full amounts of the household's lines */
        public readonly string $total,
        /** The profile's share of them */
        public readonly string $share,
    ) {
    }
}
