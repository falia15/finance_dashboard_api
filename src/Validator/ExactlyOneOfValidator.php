<?php

namespace App\Validator;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ExactlyOneOfValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ExactlyOneOf) {
            throw new UnexpectedTypeException($constraint, ExactlyOneOf::class);
        }

        if ($value === null) {
            return;
        }

        $accessor = PropertyAccess::createPropertyAccessor();
        $first = $accessor->getValue($value, $constraint->first);
        $second = $accessor->getValue($value, $constraint->second);

        if (($first === null) !== ($second === null)) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ first }}', $constraint->first)
            ->setParameter('{{ second }}', $constraint->second)
            ->atPath($constraint->first)
            ->addViolation();
    }
}
