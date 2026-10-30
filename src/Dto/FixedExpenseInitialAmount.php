<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * `initialAmount` of `POST /fixed_expenses`: the first amount of the line,
 * for one month or (`month` null) the 12 months of the year.
 */
final class FixedExpenseInitialAmount
{
    #[Assert\NotNull]
    #[Assert\Range(min: 2000, max: 2100)]
    public ?int $year = null;

    /** 1 to 12, null = the whole year */
    #[Assert\Range(min: 1, max: 12)]
    public ?int $month = null;

    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    public ?string $amount = null;

    /**
     * @return list<int> the months to fill
     */
    public function months(): array
    {
        return null === $this->month ? range(1, 12) : [$this->month];
    }
}
