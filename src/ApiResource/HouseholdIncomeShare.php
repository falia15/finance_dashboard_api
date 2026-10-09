<?php

namespace App\ApiResource;

/**
 * A household income row with the profile's share of it, in
 * ProfileIncomeMonth::$householdShares. Rows assigned to another member
 * are listed too, with a `0.00` share.
 */
final class HouseholdIncomeShare
{
    public function __construct(
        public readonly int $incomeId,
        /** Household IRI */
        public readonly string $household,
        /** IncomeType IRI */
        public readonly string $type,
        public readonly string $amount,
        public readonly int $appliedDivisor,
        /** HouseholdMember IRI receiving the whole amount, null = equal split */
        public readonly ?string $member,
        public readonly string $share,
    ) {
    }
}
