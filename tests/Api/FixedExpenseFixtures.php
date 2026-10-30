<?php

namespace App\Tests\Api;

use App\Entity\FixedExpense;
use App\Entity\FixedExpenseAmount;
use App\Entity\Household;
use App\Entity\Profile;

/**
 * Builds fixed expense lines and amounts straight in the database (no API
 * call), for tests extending ApiTestCase.
 */
trait FixedExpenseFixtures
{
    private function createFixedExpense(
        ?Profile $profile = null,
        ?Household $household = null,
        string $label = 'Rent',
        int $startYear = 2026,
        int $sortOrder = 0,
    ): FixedExpense {
        return $this->persist((new FixedExpense())
            ->setLabel($label)
            ->setProfile($profile)
            ->setHousehold($household)
            ->setStartYear($startYear)
            ->setSortOrder($sortOrder));
    }

    private function createAmount(FixedExpense $fixedExpense, int $year, int $month, string $amount = '560.00'): FixedExpenseAmount
    {
        return $this->persist((new FixedExpenseAmount())
            ->setFixedExpense($fixedExpense)
            ->setYear($year)
            ->setMonth($month)
            ->setAmount($amount));
    }

    /**
     * @return array<int, string> the line's amounts of the year, by month — read from the database
     */
    private function amountsOf(FixedExpense $fixedExpense, int $year): array
    {
        $this->entityManager()->clear();

        $amounts = [];
        foreach ($this->entityManager()->getRepository(FixedExpenseAmount::class)->findBy(['fixedExpense' => $fixedExpense->getId(), 'year' => $year], ['month' => 'ASC']) as $amount) {
            $amounts[$amount->getMonth()] = $amount->getAmount();
        }

        return $amounts;
    }
}
