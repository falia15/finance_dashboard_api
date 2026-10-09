<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class CompleteIncomeTypeOrder extends Constraint
{
    public string $message = 'The order must list every income type exactly once.';
}
