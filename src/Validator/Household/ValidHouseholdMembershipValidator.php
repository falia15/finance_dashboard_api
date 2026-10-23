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

        if ($this->isExternalOwner($value)) {
            $this->addViolation($constraint->externalOwnerMessage, 'isOwner');
        }

        if ($this->isOwnerLeaving($value)) {
            $this->addViolation($constraint->inactiveOwnerMessage, 'leftAt');
        }

        if ($this->isOwnershipRemoved($value, $original)) {
            $this->addViolation($constraint->ownerRequiredMessage, 'isOwner');
        }

        if ($this->isLeftAtChangedAfterLeaving($value, $original)) {
            $this->addViolation($constraint->leftAtLockedMessage, 'leftAt');
        } elseif ($this->isLeftBeforeJoined($value)) {
            $this->addViolation($constraint->leftBeforeJoinedMessage, 'leftAt');
        }
    }

    /**
     * An external member has no profile.
     */
    private function isExternalOwner(HouseholdMember $member): bool
    {
        return $member->isOwner() && null === $member->getProfile();
    }

    private function isOwnerLeaving(HouseholdMember $member): bool
    {
        return $member->isOwner() && null !== $member->getLeftAt();
    }

    /**
     * Ownership can only be transferred by making another member owner, never just removed.
     */
    private function isOwnershipRemoved(HouseholdMember $member, array $original): bool
    {
        $wasOwner = $original['isOwner'] ?? false;
        return $wasOwner && !$member->isOwner();
    }

    /**
     * Once a member has left, their departure date is locked.
     */
    private function isLeftAtChangedAfterLeaving(HouseholdMember $member, array $original): bool
    {
        $previousLeftAt = $original['leftAt'] ?? null;
        return null !== $previousLeftAt && $previousLeftAt != $member->getLeftAt();
    }

    private function isLeftBeforeJoined(HouseholdMember $member): bool
    {
        return null !== $member->getLeftAt()
            && null !== $member->getJoinedAt()
            && $member->getLeftAt() < $member->getJoinedAt();
    }

    private function addViolation(string $message, string $path): void
    {
        $this->context->buildViolation($message)->atPath($path)->addViolation();
    }
}
