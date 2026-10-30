<?php

namespace App\Tests\Api;

use App\Entity\FixedExpense;
use App\Entity\FixedExpenseAmount;
use App\Entity\Profile;

/**
 * Covers `POST /fixed_expenses/{id}/apply_to_year`.
 */
final class FixedExpenseApplyToYearTest extends ApiTestCase
{
    use FixedExpenseFixtures;
    use HouseholdFixtures;

    private Profile $alice;
    private FixedExpense $rent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->alice = $this->persist((new Profile())->setName('Alice'));
        $this->rent = $this->createFixedExpense(profile: $this->alice);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function applyToYear(int $year, int $month, ?FixedExpense $line = null, ?Profile $as = null): ?array
    {
        $line ??= $this->rent;

        return $this->request('POST', sprintf('/api/fixed_expenses/%d/apply_to_year', $line->getId()), ['year' => $year, 'month' => $month], self::activeProfile($as ?? $this->alice));
    }

    public function testFillsEmptyMonthsAndOverwritesFilledOnes(): void
    {
        $this->createAmount($this->rent, 2026, 1, '560.00');
        $this->createAmount($this->rent, 2026, 6, '600.00');

        $body = $this->applyToYear(2026, 1);

        self::assertResponseStatusCodeSame(200);
        self::assertSame(array_fill(1, 12, '560.00'), $this->amountsOf($this->rent, 2026));
        self::assertSame('/api/fixed_expenses/'.$this->rent->getId(), $body['fixedExpense']);
        self::assertSame(range(1, 12), array_column($body['amounts'], 'month'));
        self::assertSame(array_fill(0, 12, '560.00'), array_column($body['amounts'], 'amount'));
    }

    public function testOtherYearsAreLeftAlone(): void
    {
        $this->createAmount($this->rent, 2026, 3);
        $this->createAmount($this->rent, 2027, 1, '600.00');

        $this->applyToYear(2026, 3);

        self::assertSame([1 => '600.00'], $this->amountsOf($this->rent, 2027));
    }

    public function testStopsAtTheEndOfADeactivatedLine(): void
    {
        $this->rent->setEndYear(2026)->setEndMonth(9);
        $this->entityManager()->flush();
        $this->createAmount($this->rent, 2026, 2);

        $body = $this->applyToYear(2026, 2);

        self::assertResponseStatusCodeSame(200);
        self::assertSame(range(1, 9), array_keys($this->amountsOf($this->rent, 2026)));
        self::assertCount(9, $body['amounts']);
    }

    public function testEmptySourceMonthIsRejected(): void
    {
        $this->createAmount($this->rent, 2026, 1);

        $body = $this->applyToYear(2026, 2);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(['month'], self::violationPaths($body));
        self::assertSame([1 => '560.00'], $this->amountsOf($this->rent, 2026));
    }

    public function testYearBeforeTheStartIsRejected(): void
    {
        $body = $this->applyToYear(2025, 1);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(['month'], self::violationPaths($body));
    }

    public function testMonthOutOfRangeIsRejected(): void
    {
        $body = $this->applyToYear(2026, 13);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(['month'], self::violationPaths($body));
    }

    public function testLineOfAnotherProfileIsNotFound(): void
    {
        $bob = $this->persist((new Profile())->setName('Bob'));
        $this->createAmount($this->rent, 2026, 1);

        $this->applyToYear(2026, 1, as: $bob);

        self::assertResponseStatusCodeSame(404);
        self::assertSame([1 => '560.00'], $this->amountsOf($this->rent, 2026));
    }

    public function testOverwrittenMonthsKeepTheirDivisorNewOnesTakeTheCurrentOne(): void
    {
        $household = $this->createHousehold($this->alice);
        $line = $this->createFixedExpense(household: $household);
        $this->createAmount($line, 2026, 1);
        $this->addMember($household, externalLabel: 'Sam');

        $this->applyToYear(2026, 1, $line);

        self::assertResponseStatusCodeSame(200);
        $divisors = [];
        foreach ($this->entityManager()->getRepository(FixedExpenseAmount::class)->findBy(['fixedExpense' => $line->getId()], ['month' => 'ASC']) as $amount) {
            $divisors[$amount->getMonth()] = $amount->getAppliedDivisor();
        }
        self::assertSame([1 => 1] + array_fill(2, 11, 2), $divisors);
    }
}
