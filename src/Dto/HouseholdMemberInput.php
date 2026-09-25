<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Payload of `POST /households/{householdId}/members`. MVP: external
 * members only — V2 adds a `profileId` alternative.
 */
final class HouseholdMemberInput
{
    #[Assert\NotBlank(normalizer: 'trim')]
    #[Assert\Length(max: 255)]
    public ?string $externalLabel = null;
}
