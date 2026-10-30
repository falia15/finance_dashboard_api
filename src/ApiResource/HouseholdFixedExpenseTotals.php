<?php

namespace App\ApiResource;

/** Year totals of HouseholdFixedExpenseGrid. */
final class HouseholdFixedExpenseTotals
{
    public function __construct(
        /** Full amounts of the household's lines */
        public readonly string $total,
        /** The profile's share of them */
        public readonly string $share,
    ) {
    }
}
