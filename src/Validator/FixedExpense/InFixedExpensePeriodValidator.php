<?php

namespace App\Validator\FixedExpense;

use App\Entity\FixedExpenseAmount;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Checks the month of a fixed expense amount against FixedExpense::covers().
 * Missing fields are already reported by their own NotNull constraints.
 */
class InFixedExpensePeriodValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof InFixedExpensePeriod) {
            throw new UnexpectedTypeException($constraint, InFixedExpensePeriod::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof FixedExpenseAmount) {
            throw new UnexpectedValueException($value, FixedExpenseAmount::class);
        }

        if ($this->isIncomplete($value)) {
            return;
        }

        if (!$value->getFixedExpense()->covers($value->getYear(), $value->getMonth())) {
            $this->context->buildViolation($constraint->message)->atPath('month')->addViolation();
        }
    }

    private function isIncomplete(FixedExpenseAmount $amount): bool
    {
        return null === $amount->getFixedExpense() || null === $amount->getYear() || null === $amount->getMonth();
    }
}
