<?php

namespace App\Security;

use App\Entity\Household;
use App\Entity\HouseholdMember;
use App\Http\ActiveProfileResolver;
use App\Repository\HouseholdMemberRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Household permissions of the active profile (`X-Profile-Id`), not of an
 * authenticated user — there are no accounts in the MVP.
 *
 * - VIEW: the active profile is an active real member of the household.
 * - MANAGE: the active profile is its active owner (rename, delete, add,
 *   detach or delete members, transfer ownership).
 *
 * @extends Voter<self::VIEW|self::MANAGE, Household|HouseholdMember>
 */
final class HouseholdVoter extends Voter
{
    public const VIEW = 'HOUSEHOLD_VIEW';
    public const MANAGE = 'HOUSEHOLD_MANAGE';

    public function __construct(
        private readonly ActiveProfileResolver $activeProfileResolver,
        private readonly HouseholdMemberRepository $householdMemberRepository,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::MANAGE], true)
            && ($subject instanceof Household || $subject instanceof HouseholdMember);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $household = $subject instanceof HouseholdMember ? $subject->getHousehold() : $subject;
        if (null === $household) {
            return false;
        }

        $membership = $this->householdMemberRepository->findActiveMembership($household, $this->activeProfileResolver->getActiveProfile());

        return null !== $membership && (self::VIEW === $attribute || $membership->isOwner());
    }
}
