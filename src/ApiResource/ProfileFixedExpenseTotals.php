<?php

namespace App\ApiResource;

/** Year totals of ProfileFixedExpenseYear. */
final class ProfileFixedExpenseTotals
{
    public function __construct(
        public readonly string $individual,
        public readonly string $householdShare,
        public readonly string $total,
    ) {
    }
}
