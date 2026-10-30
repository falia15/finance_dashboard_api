<?php

namespace App\Validator\Common;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class ExactlyOneOf extends Constraint
{
    public string $message = 'Exactly one of "{{ first }}" or "{{ second }}" must be set.';

    public function __construct(
        public readonly string $first,
        public readonly string $second,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
