<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Owner and departure rules of a HouseholdMember — see the validator.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class ValidHouseholdMembership extends Constraint
{
    public string $externalOwnerMessage = 'An external member cannot own a household.';
    public string $inactiveOwnerMessage = 'The owner must be an active member: transfer ownership before leaving.';
    public string $ownerRequiredMessage = 'A household must keep its owner: set "isOwner" on another member to transfer ownership.';
    public string $leftBeforeJoinedMessage = 'A member cannot leave before having joined.';
    public string $leftAtLockedMessage = 'A member who left cannot rejoin nor change the departure date.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
