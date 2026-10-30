<?php

namespace App\ApiResource;

/**
 * A filled month of an individual fixed expense line, in
 * ProfileFixedExpenseLine::$amounts (and the apply-to-year response).
 */
final class FixedExpenseMonthAmount
{
    public function __construct(
        /** FixedExpenseAmount id */
        public readonly int $id,
        /** 1 to 12 */
        public readonly int $month,
        public readonly string $amount,
    ) {
    }
}
