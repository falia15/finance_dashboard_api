<?php

namespace App\Validator\IncomeType;

use App\Entity\IncomeType;
use App\Repository\IncomeRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * An income type is only deleted once no income uses it — no reassignment
 * for now, the user changes those incomes first.
 */
class IncomeTypeNotInUseValidator extends ConstraintValidator
{
    public function __construct(private readonly IncomeRepository $incomeRepository)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof IncomeTypeNotInUse) {
            throw new UnexpectedTypeException($constraint, IncomeTypeNotInUse::class);
        }

        if (!$value instanceof IncomeType) {
            throw new UnexpectedValueException($value, IncomeType::class);
        }

        $count = $this->incomeRepository->count(['type' => $value]);
        if ($count > 0) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ count }}', (string) $count)
                ->addViolation();
        }
    }
}
