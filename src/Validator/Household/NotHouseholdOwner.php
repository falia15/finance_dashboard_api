<?php

namespace App\Validator\Household;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class NotHouseholdOwner extends Constraint
{
    public string $message = 'The household owner cannot be removed: delete the household instead.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
