<?php

namespace App\Validator\Household;

use Symfony\Component\Validator\Constraint;

/**
 * The household member a row (expense, income, fixed expense) belongs to;
 * null splits it equally — see the validator.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class ValidRowOwnerMember extends Constraint
{
    public string $householdRequiredMessage = 'A member can only be set on a household row.';
    public string $otherHouseholdMessage = 'The member must belong to the row\'s household.';
    public string $inactiveMemberMessage = 'The member must be an active member of the household.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
