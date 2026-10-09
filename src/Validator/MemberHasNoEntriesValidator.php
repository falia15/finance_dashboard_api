<?php

namespace App\Validator;

use App\Entity\Expense;
use App\Entity\FixedExpense;
use App\Entity\HouseholdMember;
use App\Entity\Income;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * A member a row is assigned to (`member`) is never deleted: the row
 * would silently fall back to an equal split. Detach it (`leftAt`) instead.
 */
class MemberHasNoEntriesValidator extends ConstraintValidator
{
    private const ENTRY_CLASSES = [Income::class, Expense::class, FixedExpense::class];

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof MemberHasNoEntries) {
            throw new UnexpectedTypeException($constraint, MemberHasNoEntries::class);
        }

        if (!$value instanceof HouseholdMember) {
            throw new UnexpectedValueException($value, HouseholdMember::class);
        }

        foreach (self::ENTRY_CLASSES as $entryClass) {
            if ($this->entityManager->getRepository($entryClass)->count(['member' => $value]) > 0) {
                $this->context->buildViolation($constraint->message)
                    ->addViolation();

                return;
            }
        }
    }
}
