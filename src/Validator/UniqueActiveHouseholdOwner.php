<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class UniqueActiveHouseholdOwner extends Constraint
{
    public string $message = 'This household already has an active owner.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
