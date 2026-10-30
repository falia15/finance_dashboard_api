<?php

namespace App\Dto;

use ApiPlatform\Metadata\ApiProperty;
use App\ApiResource\FixedExpenseMonthAmount;

/**
 * Response of `POST /fixed_expenses/{id}/apply_to_year`: the line's amounts
 * of the year after the copy, by month.
 */
final class FixedExpenseApplyToYearOutput
{
    public function __construct(
        /** FixedExpense IRI */
        public readonly string $fixedExpense,
        public readonly int $year,
        /** @var list<FixedExpenseMonthAmount> */
        #[ApiProperty(genId: false)]
        public readonly array $amounts,
    ) {
    }
}
