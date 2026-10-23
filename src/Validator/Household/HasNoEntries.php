<?php

namespace App\Validator\Household;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class HasNoEntries extends Constraint
{
    public string $message = 'This {{ field }} is still referenced by incomes, expenses or fixed expenses and cannot be deleted.';

    public function __construct(
        public readonly string $field,
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
