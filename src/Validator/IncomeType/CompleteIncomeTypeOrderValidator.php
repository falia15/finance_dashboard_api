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

        $types = is_array($value) ? $value : [];

        if (!$this->listsEveryIncomeTypeOnce($types)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }

    /**
     * @param IncomeType[] $types
     */
    private function listsEveryIncomeTypeOnce(array $types): bool
    {
        return $this->getSortedIds($types) === $this->getSortedIds($this->incomeTypeRepository->findAll());
    }

    /**
     * @param IncomeType[] $types
     */
    private function getSortedIds(array $types): array
    {
        $ids = array_map(static fn (IncomeType $type) => $type->getId(), $types);
        sort($ids);

        return $ids;
    }
}
