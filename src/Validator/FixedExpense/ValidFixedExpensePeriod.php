<?php

namespace App\Validator\FixedExpense;

use Symfony\Component\Validator\Constraint;

/**
 * The active period of a fixed expense line (`startYear` → `endYear`/
 * `endMonth`) is consistent, and its `initialAmount` falls inside it.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class ValidFixedExpensePeriod extends Constraint
{
    public string $incompleteEndMessage = 'The end year and month must be set together.';
    public string $endBeforeStartMessage = 'The end year can\'t be before the start year.';
    public string $initialAmountBeforeStartMessage = 'The initial amount can\'t be before the start year.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
