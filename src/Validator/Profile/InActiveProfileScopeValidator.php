<?php

namespace App\Validator\Profile;

use App\Entity\Household;
use App\Entity\Profile;
use App\Http\ActiveProfileResolver;
use App\Repository\HouseholdMemberRepository;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
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

        // The profile, when set, must be the active one
        $profile = $accessor->getValue($value, 'profile');
        if (null !== $profile && !$this->isActiveProfile($profile, $activeProfile)) {
            $this->context->buildViolation($constraint->profileMessage)
                ->atPath('profile')
                ->addViolation();
        }

        // The household, when set, must be one the active profile is a member of
        $household = $this->getHousehold($accessor, $value);
        if (null !== $household && !$this->isActiveMember($household, $activeProfile)) {
            $this->context->buildViolation($constraint->householdMessage)
                ->atPath('household')
                ->addViolation();
        }
    }

    private function isActiveProfile(Profile $profile, Profile $activeProfile): bool
    {
        return $profile->getId() === $activeProfile->getId();
    }

    private function isActiveMember(Household $household, Profile $profile): bool
    {
        return null !== $this->householdMemberRepository->findActiveMembership($household, $profile);
    }

    /**
     * Null when the entity has no `household` field.
     */
    private function getHousehold(PropertyAccessorInterface $accessor, object $value): ?Household
    {
        return $accessor->isReadable($value, 'household') ? $accessor->getValue($value, 'household') : null;
    }
}
