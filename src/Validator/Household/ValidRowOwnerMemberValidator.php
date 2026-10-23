<?php

namespace App\Validator\Household;

use App\Entity\HouseholdMember;
use App\Entity\HouseholdRowInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Checks the member a row belongs to. 
 * - A row is one line of the Expense, Income or FixedExpense tables; 
 * - On a household row, `householdMember` says which member it belongs to, 
 * and null means it is split equally between the members.
 */
class ValidRowOwnerMemberValidator extends ConstraintValidator
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidRowOwnerMember) {
            throw new UnexpectedTypeException($constraint, ValidRowOwnerMember::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof HouseholdRowInterface) {
            throw new UnexpectedValueException($value, HouseholdRowInterface::class);
        }

        // No member: the row is personal or split equally, nothing to check
        $member = $value->getHouseholdMember();
        if (null === $member) {
            return;
        }

        // A member can only be set on a household row
        $household = $value->getHousehold();
        if (null === $household) {
            $this->addViolation($constraint->householdRequiredMessage);
            return;
        }

        // The member must belong to the row's household
        if ($member->getHousehold()?->getId() !== $household->getId()) {
            $this->addViolation($constraint->otherHouseholdMessage);
            return;
        }

        // A member who left can't be picked, but rows already theirs keep them
        if ($this->isNewlyPicked($value, $member) && $this->hasLeft($member)) {
            $this->addViolation($constraint->inactiveMemberMessage);
        }
    }

    /**
     * True when the member differs from the one stored in the database, or the
     * row is being created.
     */
    private function isNewlyPicked(HouseholdRowInterface $row, HouseholdMember $member): bool
    {
        $original = $this->entityManager->getUnitOfWork()->getOriginalEntityData($row);

        return ($original['householdMember'] ?? null) !== $member;
    }

    private function hasLeft(HouseholdMember $member): bool
    {
        return null !== $member->getLeftAt();
    }

    private function addViolation(string $message): void
    {
        $this->context->buildViolation($message)->atPath('householdMember')->addViolation();
    }
}
