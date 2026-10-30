<?php

namespace App\Validator\FixedExpense;

use App\Entity\FixedExpense;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Checks the period of a fixed expense line. A missing `startYear` is
 * already reported by its own NotNull constraint.
 */
class ValidFixedExpensePeriodValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidFixedExpensePeriod) {
            throw new UnexpectedTypeException($constraint, ValidFixedExpensePeriod::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof FixedExpense) {
            throw new UnexpectedValueException($value, FixedExpense::class);
        }

        // The end is a month: year and month go together
        if ($this->hasIncompleteEnd($value)) {
            $this->addViolation($constraint->incompleteEndMessage, 'endMonth');
        }

        if (null === $value->getStartYear()) {
            return;
        }

        // A line can't end before it starts
        if ($this->endsBeforeStart($value)) {
            $this->addViolation($constraint->endBeforeStartMessage, 'endYear');
        }

        // The first amount(s) can't be before the line starts
        if ($this->hasInitialAmountBeforeStart($value)) {
            $this->addViolation($constraint->initialAmountBeforeStartMessage, 'initialAmount.year');
        }
    }

    private function hasIncompleteEnd(FixedExpense $fixedExpense): bool
    {
        return (null === $fixedExpense->getEndYear()) !== (null === $fixedExpense->getEndMonth());
    }

    private function endsBeforeStart(FixedExpense $fixedExpense): bool
    {
        return null !== $fixedExpense->getEndYear() && $fixedExpense->getEndYear() < $fixedExpense->getStartYear();
    }

    private function hasInitialAmountBeforeStart(FixedExpense $fixedExpense): bool
    {
        $year = $fixedExpense->getInitialAmount()?->year;

        return null !== $year && $year < $fixedExpense->getStartYear();
    }

    private function addViolation(string $message, string $path): void
    {
        $this->context->buildViolation($message)->atPath($path)->addViolation();
    }
}
