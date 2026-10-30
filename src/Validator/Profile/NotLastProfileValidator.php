<?php

namespace App\Validator\Profile;

use App\Entity\Profile;
use App\Repository\ProfileRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class NotLastProfileValidator extends ConstraintValidator
{
    public function __construct(private readonly ProfileRepository $profileRepository)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof NotLastProfile) {
            throw new UnexpectedTypeException($constraint, NotLastProfile::class);
        }

        if (!$value instanceof Profile) {
            throw new UnexpectedValueException($value, Profile::class);
        }

        if ($this->isLastProfile()) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }

    private function isLastProfile(): bool
    {
        return $this->profileRepository->count() <= 1;
    }
}
