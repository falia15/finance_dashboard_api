<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class NotLastProfile extends Constraint
{
    public string $message = 'The last remaining profile cannot be deleted.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
