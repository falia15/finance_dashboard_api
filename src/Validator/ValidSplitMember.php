<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Rules of the `member` field of a household row — see the validator.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class ValidSplitMember extends Constraint
{
    public string $householdRequiredMessage = 'A member can only be set on a household row.';
    public string $otherHouseholdMessage = 'The member must belong to the row\'s household.';
    public string $inactiveMemberMessage = 'The member must be an active member of the household.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
