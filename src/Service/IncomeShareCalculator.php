<?php

namespace App\Service;

use App\Entity\HouseholdMember;
use App\Entity\Income;

/**
 * A member's share of a household income row, see "Household split" in
 * backend/v1/03-business-rules.md.
 *
 * Amounts are DECIMAL(10,2) strings, computed as integer cents (no float,
 * bcmath isn't installed): the shares of a row always add up to its amount.
 */
final class IncomeShareCalculator
{
    /**
     * Share of one member when `$amount` is split equally between
     * `$divisor` members. The cents left over by the division go to the
     * member who `$absorbsRemainder` (the household owner).
     *
     * share('45.01', 2, true)  → '22.51'
     * share('45.01', 2, false) → '22.50'
     */
    public function share(string $amount, int $divisor, bool $absorbsRemainder): string
    {
        if ($divisor < 1) {
            throw new \InvalidArgumentException(sprintf('Divisor must be at least 1, got %d.', $divisor));
        }

        $cents = self::toCents($amount);
        $share = intdiv($cents, $divisor);

        return self::fromCents($absorbsRemainder ? $share + $cents % $divisor : $share);
    }

    /**
     * Share of `$viewer` (the viewing profile's membership) of a household
     * row: the whole amount if the row is assigned to them, nothing if it
     * is assigned to another member, an equal share otherwise.
     */
    public function shareFor(Income $income, HouseholdMember $viewer): string
    {
        $amount = $income->getAmount() ?? '0.00';
        $member = $income->getMember();

        if (null !== $member) {
            return $member === $viewer ? self::fromCents(self::toCents($amount)) : '0.00';
        }

        return $this->share($amount, $income->getAppliedDivisor(), $viewer->isOwner());
    }

    /** Sum of decimal amounts, e.g. sum('0.10', '0.20') → '0.30'. */
    public function sum(string ...$amounts): string
    {
        return self::fromCents(array_sum(array_map(self::toCents(...), $amounts)));
    }

    /** '2073.5' → 207350, '-0.01' → -1. */
    private static function toCents(string $amount): int
    {
        if (1 !== preg_match('/^(-)?(\d+)(?:\.(\d{1,2}))?$/', trim($amount), $matches)) {
            throw new \InvalidArgumentException(sprintf('Invalid amount "%s".', $amount));
        }

        $cents = (int) $matches[2] * 100 + (int) str_pad($matches[3] ?? '', 2, '0');

        return '-' === $matches[1] ? -$cents : $cents;
    }

    /** 207350 → '2073.50', -1 → '-0.01'. */
    private static function fromCents(int $cents): string
    {
        $absolute = abs($cents);

        return sprintf('%s%d.%02d', $cents < 0 ? '-' : '', intdiv($absolute, 100), $absolute % 100);
    }
}
