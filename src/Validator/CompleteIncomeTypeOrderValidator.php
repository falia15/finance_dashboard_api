<?php

namespace App\Validator;

use App\Entity\IncomeType;
use App\Repository\IncomeTypeRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * A reorder lists all the income types, each once — never a partial list,
 * so no type is left with an ambiguous position.
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

        $ids = array_map(static fn (IncomeType $type) => $type->getId(), \is_array($value) ? $value : []);
        $allIds = array_map(static fn (IncomeType $type) => $type->getId(), $this->incomeTypeRepository->findAll());
        sort($ids);
        sort($allIds);

        if ($ids !== $allIds) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
