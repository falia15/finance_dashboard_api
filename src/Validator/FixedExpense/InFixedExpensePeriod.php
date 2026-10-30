<?php

namespace App\Validator\FixedExpense;

use Symfony\Component\Validator\Constraint;

/**
 * A fixed expense amount must fall inside its line's active period: not
 * before `startYear`, not after the end of a deactivated line.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class InFixedExpensePeriod extends Constraint
{
    public string $message = 'This month is outside the active period of the fixed expense.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
