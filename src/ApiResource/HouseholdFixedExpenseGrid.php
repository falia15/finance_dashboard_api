<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;

/**
 * The fixed expenses of one household the profile is an active member of,
 * in ProfileFixedExpenseYear::$households.
 */
final class HouseholdFixedExpenseGrid
{
    public function __construct(
        /** Household IRI */
        public readonly string $household,
        /** @var list<HouseholdFixedExpenseLine> */
        #[ApiProperty(genId: false)]
        public readonly array $lines,
        /** @var list<HouseholdFixedExpenseMonth> always 12 */
        #[ApiProperty(genId: false)]
        public readonly array $months,
        #[ApiProperty(genId: false)]
        public readonly HouseholdFixedExpenseTotals $totals,
    ) {
    }
}
