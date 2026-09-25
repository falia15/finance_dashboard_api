<?php

namespace App\Validator;

use App\Entity\HouseholdMember;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class NotHouseholdOwnerValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof NotHouseholdOwner) {
            throw new UnexpectedTypeException($constraint, NotHouseholdOwner::class);
        }

        if (!$value instanceof HouseholdMember) {
            throw new UnexpectedValueException($value, HouseholdMember::class);
        }

        if ($value->isOwner()) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
