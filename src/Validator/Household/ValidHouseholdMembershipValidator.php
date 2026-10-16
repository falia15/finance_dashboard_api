<?php

namespace App\Validator\Household;

use App\Entity\HouseholdMember;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * - the owner is a real profile, and an active member (it can't leave);
 * - ownership is only ever transferred (isOwner set on another member,
 *   see HouseholdMemberUpdateProcessor), never just removed;
 * - `leftAt` is on or after `joinedAt`, and final once set (history).
 *
 * Previous values are read from Doctrine's original data, empty for a
 * member being created.
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
