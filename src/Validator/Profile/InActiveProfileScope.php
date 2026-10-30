<?php

namespace App\Validator\Profile;

use Symfony\Component\Validator\Constraint;

/**
 * Write-side counterpart of ActiveProfileExtension: a row can only be
 * attached to the active profile, or to a household it is an active real
 * member of.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class InActiveProfileScope extends Constraint
{
    public string $profileMessage = 'A row can only be attached to the active profile.';
    public string $householdMessage = 'A row can only be attached to a household the active profile is a member of.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
