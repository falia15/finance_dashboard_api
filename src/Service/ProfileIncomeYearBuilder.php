<?php

namespace App\Service;

use ApiPlatform\Metadata\IriConverterInterface;
use App\ApiResource\HouseholdIncomeShare;
use App\ApiResource\ProfileIncomeLine;
use App\ApiResource\ProfileIncomeMonth;
use App\ApiResource\ProfileIncomeTotals;
use App\ApiResource\ProfileIncomeYear;
use App\Entity\Profile;
use App\Repository\HouseholdMemberRepository;
use App\Repository\IncomeRepository;

/**
 * Builds a profile's year of incomes (ProfileIncomeYear): its individual
 * rows, and its share of the rows of the households it is currently an
 * active member of.
 */
final class ProfileIncomeYearBuilder
{
    public function __construct(
        private readonly IncomeRepository $incomeRepository,
        private readonly HouseholdMemberRepository $householdMemberRepository,
        private readonly HouseholdShareCalculator $calculator,
        private readonly IriConverterInterface $iriConverter,
    ) {
    }

    public function build(Profile $profile, int $year): ProfileIncomeYear
    {
        /** @var array<int, list<ProfileIncomeLine>> $incomesByMonth */
        $incomesByMonth = [];
        foreach ($this->incomeRepository->findIndividualForYear($profile, $year) as $income) {
            $incomesByMonth[$income->getMonth()][] = new ProfileIncomeLine(
                $income->getId(),
                $this->iriConverter->getIriFromResource($income->getType()),
                $income->getAmount(),
            );
        }

        $memberships = [];
        foreach ($this->householdMemberRepository->findActiveMembershipsOf($profile) as $membership) {
            $memberships[$membership->getHousehold()->getId()] = $membership;
        }

        /** @var array<int, list<HouseholdIncomeShare>> $sharesByMonth */
        $sharesByMonth = [];
        $households = array_values(array_map(static fn ($membership) => $membership->getHousehold(), $memberships));
        foreach ($this->incomeRepository->findForHouseholdsAndYear($households, $year) as $income) {
            $household = $income->getHousehold();
            $sharesByMonth[$income->getMonth()][] = new HouseholdIncomeShare(
                $income->getId(),
                $this->iriConverter->getIriFromResource($household),
                $this->iriConverter->getIriFromResource($income->getType()),
                $income->getAmount(),
                $income->getAppliedDivisor(),
                null === $income->getHouseholdMember() ? null : $this->iriConverter->getIriFromResource($income->getHouseholdMember()),
                $this->calculator->shareFor($income->getAmount(), $income->getAppliedDivisor(), $income->getHouseholdMember(), $memberships[$household->getId()]),
            );
        }

        $months = [];
        $individualTotals = [];
        $shareTotals = [];
        for ($month = 1; $month <= 12; ++$month) {
            $incomes = $incomesByMonth[$month] ?? [];
            $shares = $sharesByMonth[$month] ?? [];
            $individualTotals[] = $individual = $this->calculator->sum(...array_map(static fn ($line) => $line->amount, $incomes));
            $shareTotals[] = $share = $this->calculator->sum(...array_map(static fn ($line) => $line->share, $shares));

            $months[] = new ProfileIncomeMonth($month, $incomes, $shares, $this->calculator->sum($individual, $share));
        }

        $individual = $this->calculator->sum(...$individualTotals);
        $householdShare = $this->calculator->sum(...$shareTotals);

        return new ProfileIncomeYear(
            $profile->getId(),
            $year,
            $months,
            new ProfileIncomeTotals($individual, $householdShare, $this->calculator->sum($individual, $householdShare)),
        );
    }
}
