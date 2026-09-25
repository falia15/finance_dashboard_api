<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class HouseholdHasNoEntries extends Constraint
{
    public string $message = 'This household still has incomes, expenses or fixed expenses attached and cannot be deleted.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
