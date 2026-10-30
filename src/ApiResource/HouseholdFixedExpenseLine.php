<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;

/**
 * A household fixed expense line with the profile's share of each month,
 * in HouseholdFixedExpenseGrid::$lines. Lines assigned to another member
 * are listed too, with `0.00` shares.
 */
final class HouseholdFixedExpenseLine
{
    public function __construct(
        /** FixedExpense id */
        public readonly int $id,
        public readonly string $label,
        public readonly int $sortOrder,
        /** HouseholdMember IRI paying the whole line, null = equal split */
        public readonly ?string $householdMember,
        public readonly int $startYear,
        public readonly ?int $endYear,
        public readonly ?int $endMonth,
        /** @var list<FixedExpenseMonthShare> filled months only */
        #[ApiProperty(genId: false)]
        public readonly array $amounts,
        /** Full amounts of the year */
        public readonly string $total,
        /** The profile's shares of the year */
        public readonly string $shareTotal,
    ) {
    }
}
