<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Payload of `POST /fixed_expenses/{id}/apply_to_year`: the month whose
 * amount is copied to the whole year.
 */
final class FixedExpenseApplyToYearInput
{
    #[Assert\NotNull]
    #[Assert\Range(min: 2000, max: 2100)]
    public ?int $year = null;

    #[Assert\NotNull]
    #[Assert\Range(min: 1, max: 12)]
    public ?int $month = null;
}
