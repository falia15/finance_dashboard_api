<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;

/** One month of ProfileIncomeYear. */
final class ProfileIncomeMonth
{
    public function __construct(
        /** 1 to 12 */
        public readonly int $month,
        /** @var list<ProfileIncomeLine> */
        #[ApiProperty(genId: false)]
        public readonly array $incomes,
        /** @var list<HouseholdIncomeShare> */
        #[ApiProperty(genId: false)]
        public readonly array $householdShares,
        /** Individual incomes + household shares of the month */
        public readonly string $total,
    ) {
    }
}
