<?php

namespace App\Validator\Household;

use App\Entity\HouseholdMember;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * `member` (null = split equally) is only set on a household row, and
 * points to a member of that household. The member must be active when
 * it is picked — a row keeps its member after that member leaves.
 *
 * The previous member is read from Doctrine's original data, empty for a
 * row being created.
 */
class ValidSplitMemberValidator extends ConstraintValidator
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidSplitMember) {
            throw new UnexpectedTypeException($constraint, ValidSplitMember::class);
        }

        if (null === $value) {
            return;
        }

        $accessor = PropertyAccess::createPropertyAccessor();
        /** @var HouseholdMember|null $member */
        $member = $accessor->getValue($value, 'member');
        if (null === $member) {
            return;
        }

        $household = $accessor->getValue($value, 'household');
        if (null === $household) {
            $this->addViolation($constraint->householdRequiredMessage);

            return;
        }

        if ($member->getHousehold()?->getId() !== $household->getId()) {
            $this->addViolation($constraint->otherHouseholdMessage);

            return;
        }

        $original = $this->entityManager->getUnitOfWork()->getOriginalEntityData($value);
        if (($original['member'] ?? null) !== $member && null !== $member->getLeftAt()) {
            $this->addViolation($constraint->inactiveMemberMessage);
        }
    }

    private function addViolation(string $message): void
    {
        $this->context->buildViolation($message)->atPath('member')->addViolation();
    }
}
