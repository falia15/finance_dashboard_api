<?php

namespace App\ApiResource;

/** Year totals of ProfileIncomeYear. */
final class ProfileIncomeTotals
{
    public function __construct(
        public readonly string $individual,
        public readonly string $householdShare,
        public readonly string $total,
    ) {
    }
}
