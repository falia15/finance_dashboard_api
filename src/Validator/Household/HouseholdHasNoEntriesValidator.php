<?php

namespace App\Validator\Household;

use App\Entity\Expense;
use App\Entity\FixedExpense;
use App\Entity\Household;
use App\Entity\Income;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * A household is only deleted once nothing references it anymore — its
 * rows are never cascaded nor silently moved to a profile.
 */
class HouseholdHasNoEntriesValidator extends ConstraintValidator
{
    private const ENTRY_CLASSES = [Income::class, Expense::class, FixedExpense::class];

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof HouseholdHasNoEntries) {
            throw new UnexpectedTypeException($constraint, HouseholdHasNoEntries::class);
        }

        if (!$value instanceof Household) {
            throw new UnexpectedValueException($value, Household::class);
        }

        foreach (self::ENTRY_CLASSES as $entryClass) {
            if ($this->entityManager->getRepository($entryClass)->count(['household' => $value]) > 0) {
                $this->context->buildViolation($constraint->message)
                    ->addViolation();

                return;
            }
        }
    }
}
