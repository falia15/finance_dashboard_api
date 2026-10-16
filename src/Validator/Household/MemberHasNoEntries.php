<?php

namespace App\Validator\Household;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class MemberHasNoEntries extends Constraint
{
    public string $message = 'This member is still assigned to incomes, expenses or fixed expenses and cannot be deleted.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
