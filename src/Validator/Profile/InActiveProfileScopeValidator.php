<?php

namespace App\Validator\Profile;

use App\Http\ActiveProfileResolver;
use App\Repository\HouseholdMemberRepository;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Checks the `profile` and, when the entity has one, `household` fields.
 */
class InActiveProfileScopeValidator extends ConstraintValidator
{
    public function __construct(
        private readonly ActiveProfileResolver $activeProfileResolver,
        private readonly HouseholdMemberRepository $householdMemberRepository,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof InActiveProfileScope) {
            throw new UnexpectedTypeException($constraint, InActiveProfileScope::class);
        }

        if (null === $value) {
            return;
        }

        $accessor = PropertyAccess::createPropertyAccessor();
        $activeProfile = $this->activeProfileResolver->getActiveProfile();

        $profile = $accessor->getValue($value, 'profile');
        if (null !== $profile && $profile->getId() !== $activeProfile->getId()) {
            $this->context->buildViolation($constraint->profileMessage)
                ->atPath('profile')
                ->addViolation();
        }

        $household = $accessor->isReadable($value, 'household') ? $accessor->getValue($value, 'household') : null;
        if (null !== $household && null === $this->householdMemberRepository->findActiveMembership($household, $activeProfile)) {
            $this->context->buildViolation($constraint->householdMessage)
                ->atPath('household')
                ->addViolation();
        }
    }
}
