<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class IncomeTypeNotInUse extends Constraint
{
    public string $message = 'This income type is used by {{ count }} income(s) and cannot be deleted.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
