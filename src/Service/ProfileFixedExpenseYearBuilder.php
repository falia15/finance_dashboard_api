<?php

namespace App\Service;

use ApiPlatform\Metadata\IriConverterInterface;
use App\ApiResource\FixedExpenseMonthAmount;
use App\ApiResource\FixedExpenseMonthShare;
use App\ApiResource\HouseholdFixedExpenseGrid;
use App\ApiResource\HouseholdFixedExpenseLine;
use App\ApiResource\HouseholdFixedExpenseMonth;
use App\ApiResource\HouseholdFixedExpenseTotals;
use App\ApiResource\ProfileFixedExpenseLine;
use App\ApiResource\ProfileFixedExpenseMonth;
use App\ApiResource\ProfileFixedExpenseTotals;
use App\ApiResource\ProfileFixedExpenseYear;
use App\Entity\FixedExpense;
use App\Entity\FixedExpenseAmount;
use App\Entity\HouseholdMember;
use App\Entity\Profile;
use App\Repository\FixedExpenseAmountRepository;
use App\Repository\FixedExpenseRepository;
use App\Repository\HouseholdMemberRepository;

/**
 * Builds a profile's year of fixed expenses (ProfileFixedExpenseYear): its
 * individual lines, and one grid per household it is currently an active
 * member of, with its share of each month.
 */
final class ProfileFixedExpenseYearBuilder
{
    public function __construct(
        private readonly FixedExpenseRepository $fixedExpenseRepository,
        private readonly FixedExpenseAmountRepository $fixedExpenseAmountRepository,
        private readonly HouseholdMemberRepository $householdMemberRepository,
        private readonly HouseholdShareCalculator $calculator,
        private readonly IriConverterInterface $iriConverter,
    ) {
    }

    public function build(Profile $profile, int $year): ProfileFixedExpenseYear
    {
        $memberships = [];
        foreach ($this->householdMemberRepository->findActiveMembershipsOf($profile) as $membership) {
            $memberships[$membership->getHousehold()->getId()] = $membership;
        }
        ksort($memberships);

        $individualLines = $this->fixedExpenseRepository->findIndividualShownIn($profile, $year);
        $householdLines = $this->fixedExpenseRepository->findForHouseholdsShownIn(
            array_values(array_map(static fn (HouseholdMember $membership) => $membership->getHousehold(), $memberships)),
            $year,
        );

        /** @var array<int, list<FixedExpenseAmount>> $amountsByLine */
        $amountsByLine = [];
        foreach ($this->fixedExpenseAmountRepository->findForLinesAndYear([...$individualLines, ...$householdLines], $year) as $amount) {
            $amountsByLine[$amount->getFixedExpense()->getId()][] = $amount;
        }

        /** @var array<int, list<string>> $individualByMonth */
        $individualByMonth = [];
        $lines = [];
        foreach ($individualLines as $line) {
            $amounts = $amountsByLine[$line->getId()] ?? [];
            foreach ($amounts as $amount) {
                $individualByMonth[$amount->getMonth()][] = $amount->getAmount();
            }
            $lines[] = $this->individualLine($line, $amounts);
        }

        /** @var array<int, list<FixedExpense>> $linesByHousehold */
        $linesByHousehold = [];
        foreach ($householdLines as $line) {
            $linesByHousehold[$line->getHousehold()->getId()][] = $line;
        }

        /** @var array<int, list<string>> $shareByMonth */
        $shareByMonth = [];
        $grids = [];
        foreach ($memberships as $householdId => $membership) {
            $grids[] = $grid = $this->householdGrid($membership, $linesByHousehold[$householdId] ?? [], $amountsByLine);
            foreach ($grid->months as $month) {
                $shareByMonth[$month->month][] = $month->share;
            }
        }

        $months = [];
        for ($month = 1; $month <= 12; ++$month) {
            $individual = $this->calculator->sum(...$individualByMonth[$month] ?? []);
            $share = $this->calculator->sum(...$shareByMonth[$month] ?? []);
            $months[] = new ProfileFixedExpenseMonth($month, $individual, $share, $this->calculator->sum($individual, $share));
        }

        $individual = $this->calculator->sum(...array_map(static fn (ProfileFixedExpenseMonth $month) => $month->individual, $months));
        $householdShare = $this->calculator->sum(...array_map(static fn (ProfileFixedExpenseMonth $month) => $month->householdShare, $months));

        return new ProfileFixedExpenseYear(
            $profile->getId(),
            $year,
            $lines,
            $grids,
            $months,
            new ProfileFixedExpenseTotals($individual, $householdShare, $this->calculator->sum($individual, $householdShare)),
        );
    }

    /**
     * @param list<FixedExpenseAmount> $amounts the line's amounts of the year
     */
    private function individualLine(FixedExpense $line, array $amounts): ProfileFixedExpenseLine
    {
        return new ProfileFixedExpenseLine(
            $line->getId(),
            $line->getLabel(),
            $line->getSortOrder(),
            $line->getStartYear(),
            $line->getEndYear(),
            $line->getEndMonth(),
            array_map(static fn (FixedExpenseAmount $amount) => new FixedExpenseMonthAmount($amount->getId(), $amount->getMonth(), $amount->getAmount()), $amounts),
            $this->calculator->sum(...array_map(static fn (FixedExpenseAmount $amount) => $amount->getAmount(), $amounts)),
        );
    }

    /**
     * @param list<FixedExpense>                     $lines         the household's lines shown for the year
     * @param array<int, list<FixedExpenseAmount>> $amountsByLine amounts of the year, by line id
     */
    private function householdGrid(HouseholdMember $membership, array $lines, array $amountsByLine): HouseholdFixedExpenseGrid
    {
        /** @var array<int, list<string>> $totalByMonth */
        $totalByMonth = [];
        /** @var array<int, list<string>> $shareByMonth */
        $shareByMonth = [];
        $gridLines = [];
        foreach ($lines as $line) {
            $shares = [];
            foreach ($amountsByLine[$line->getId()] ?? [] as $amount) {
                $shares[] = $share = new FixedExpenseMonthShare(
                    $amount->getId(),
                    $amount->getMonth(),
                    $amount->getAmount(),
                    $amount->getAppliedDivisor(),
                    $this->calculator->shareFor($amount->getAmount(), $amount->getAppliedDivisor(), $line->getHouseholdMember(), $membership),
                );
                $totalByMonth[$share->month][] = $share->amount;
                $shareByMonth[$share->month][] = $share->share;
            }
            $gridLines[] = $this->householdLine($line, $shares);
        }

        $months = [];
        for ($month = 1; $month <= 12; ++$month) {
            $months[] = new HouseholdFixedExpenseMonth(
                $month,
                $this->calculator->sum(...$totalByMonth[$month] ?? []),
                $this->calculator->sum(...$shareByMonth[$month] ?? []),
            );
        }

        return new HouseholdFixedExpenseGrid(
            $this->iriConverter->getIriFromResource($membership->getHousehold()),
            $gridLines,
            $months,
            new HouseholdFixedExpenseTotals(
                $this->calculator->sum(...array_map(static fn (HouseholdFixedExpenseMonth $month) => $month->total, $months)),
                $this->calculator->sum(...array_map(static fn (HouseholdFixedExpenseMonth $month) => $month->share, $months)),
            ),
        );
    }

    /**
     * @param list<FixedExpenseMonthShare> $shares the line's months of the year
     */
    private function householdLine(FixedExpense $line, array $shares): HouseholdFixedExpenseLine
    {
        return new HouseholdFixedExpenseLine(
            $line->getId(),
            $line->getLabel(),
            $line->getSortOrder(),
            null === $line->getHouseholdMember() ? null : $this->iriConverter->getIriFromResource($line->getHouseholdMember()),
            $line->getStartYear(),
            $line->getEndYear(),
            $line->getEndMonth(),
            $shares,
            $this->calculator->sum(...array_map(static fn (FixedExpenseMonthShare $share) => $share->amount, $shares)),
            $this->calculator->sum(...array_map(static fn (FixedExpenseMonthShare $share) => $share->share, $shares)),
        );
    }
}
