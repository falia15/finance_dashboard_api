<?php

namespace App\Validator\Household;

use App\Entity\Expense;
use App\Entity\FixedExpense;
use App\Entity\Income;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Prevent removing an entity still referenced by an income or an expense:
 * - A household is only deleted once nothing references it anymore.
 * - A member a row is assigned to is never deleted: the row would silently
 *   fall back to an equal split. Detach it (`leftAt`) instead.
 */
class HasNoEntriesValidator extends ConstraintValidator
{
    private const ENTRY_CLASSES = [Income::class, Expense::class, FixedExpense::class];

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ){}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof HasNoEntries) {
            throw new UnexpectedTypeException($constraint, HasNoEntries::class);
        }

        if (null === $value) {
            return;
        }

        if ($this->isReferencedByAnEntry($constraint->field, $value)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }

    /**
     * True when at least one income, expense or fixed expense has `$value` in its `$field`.
     */
    private function isReferencedByAnEntry(string $field, mixed $value): bool
    {
        foreach (self::ENTRY_CLASSES as $entryClass) {
            if ($this->entityManager->getRepository($entryClass)->count([$field => $value]) > 0) {
                return true;
            }
        }

        return false;
    }
}
