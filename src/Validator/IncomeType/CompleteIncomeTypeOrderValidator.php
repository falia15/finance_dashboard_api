<?php

namespace App\Validator\IncomeType;

use App\Entity\IncomeType;
use App\Repository\IncomeTypeRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Checks that a reorder lists every income type exactly once (order is free).
 * Prevents bugs when several users edit the income types at the same time.
 */
class CompleteIncomeTypeOrderValidator extends ConstraintValidator
{
    public function __construct(private readonly IncomeTypeRepository $incomeTypeRepository)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof CompleteIncomeTypeOrder) {
            throw new UnexpectedTypeException($constraint, CompleteIncomeTypeOrder::class);
        }

        $ids = array_map(
            static fn (IncomeType $type) => $type->getId(),
            is_array($value) ? $value : []
        );
        $allIds = array_map(
            static fn (IncomeType $type) => $type->getId(),
            $this->incomeTypeRepository->findAll()
        );
        sort($ids);
        sort($allIds);

        if ($ids !== $allIds) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
