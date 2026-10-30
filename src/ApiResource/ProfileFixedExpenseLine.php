<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;

/** An individual fixed expense line of the profile, in ProfileFixedExpenseYear::$lines. */
final class ProfileFixedExpenseLine
{
    public function __construct(
        /** FixedExpense id */
        public readonly int $id,
        public readonly string $label,
        public readonly int $sortOrder,
        public readonly int $startYear,
        public readonly ?int $endYear,
        public readonly ?int $endMonth,
        /** @var list<FixedExpenseMonthAmount> filled months only */
        #[ApiProperty(genId: false)]
        public readonly array $amounts,
        public readonly string $total,
    ) {
    }
}
