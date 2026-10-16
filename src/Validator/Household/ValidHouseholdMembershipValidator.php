<?php

namespace App\Validator\Household;

use App\Entity\HouseholdMember;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Prevent creating a householdMembership that does not respect the following rules:
 * - Give the household ownership to an external member.
 * - Make the owner leave.
 * - Leave the household without an owner.
 * - Make a member who left come back, or change their departure date.
 * - Set a departure date before the join date.
 */
class ValidHouseholdMembershipValidator extends ConstraintValidator
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidHouseholdMembership) {
            throw new UnexpectedTypeException($constraint, ValidHouseholdMembership::class);
        }

        if (!$value instanceof HouseholdMember) {
            throw new UnexpectedValueException($value, HouseholdMember::class);
        }

        $original = $this->entityManager->getUnitOfWork()->getOriginalEntityData($value);
        $wasOwner = $original['isOwner'] ?? false;
        $previousLeftAt = $original['leftAt'] ?? null;

        if ($value->isOwner() && null === $value->getProfile()) {
            $this->addViolation($constraint->externalOwnerMessage, 'isOwner');
        }

        if ($value->isOwner() && null !== $value->getLeftAt()) {
            $this->addViolation($constraint->inactiveOwnerMessage, 'leftAt');
        }

        if ($wasOwner && !$value->isOwner()) {
            $this->addViolation($constraint->ownerRequiredMessage, 'isOwner');
        }

        if (null !== $previousLeftAt && $previousLeftAt != $value->getLeftAt()) {
            $this->addViolation($constraint->leftAtLockedMessage, 'leftAt');
        } elseif (null !== $value->getLeftAt() && null !== $value->getJoinedAt() && $value->getLeftAt() < $value->getJoinedAt()) {
            $this->addViolation($constraint->leftBeforeJoinedMessage, 'leftAt');
        }
    }

    private function addViolation(string $message, string $path): void
    {
        $this->context->buildViolation($message)->atPath($path)->addViolation();
    }
}
