<?php

namespace App\Tests\Api;

use App\Entity\FixedExpense;
use App\Entity\Profile;

/**
 * Covers `/fixed_expense_amounts`: filling, correcting and emptying one
 * month, within the line's active period.
 */
final class FixedExpenseAmountTest extends ApiTestCase
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
     * @param array<string, mixed> $fields
     *
     * @return array<string, mixed>|null
     */
    private function postAmount(array $fields = [], ?Profile $as = null): ?array
    {
        return $this->request('POST', '/api/fixed_expense_amounts', $fields + [
            'fixedExpense' => '/api/fixed_expenses/'.$this->rent->getId(),
            'year' => 2026,
            'month' => 6,
            'amount' => '175.22',
        ], self::activeProfile($as ?? $this->alice));
    }

    public function testFillingAMonth(): void
    {
        $body = $this->postAmount();

        self::assertResponseStatusCodeSame(201);
        self::assertSame('175.22', $body['amount']);
        self::assertSame(1, $body['appliedDivisor']);
        self::assertSame([6 => '175.22'], $this->amountsOf($this->rent, 2026));
    }

    public function testZeroIsAFilledMonth(): void
    {
        $this->postAmount(['amount' => '0.00']);

        self::assertResponseStatusCodeSame(201);
        self::assertSame([6 => '0.00'], $this->amountsOf($this->rent, 2026));
    }

    public function testNegativeAmountIsRejected(): void
    {
        $body = $this->postAmount(['amount' => '-1.00']);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(['amount'], self::violationPaths($body));
    }

    public function testSecondAmountForTheSameMonthIsRejected(): void
    {
        $this->createAmount($this->rent, 2026, 6);

        $body = $this->postAmount();

        self::assertResponseStatusCodeSame(422);
        self::assertSame(['month'], self::violationPaths($body));
    }

    public function testMonthBeforeTheStartYearIsRejected(): void
    {
        $body = $this->postAmount(['year' => 2025]);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(['month'], self::violationPaths($body));
    }

    public function testMonthAfterTheEndIsRejected(): void
    {
        $this->rent->setEndYear(2026)->setEndMonth(5);
        $this->entityManager()->flush();

        $body = $this->postAmount(['month' => 6]);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(['month'], self::violationPaths($body));
    }

    public function testLastMonthOfADeactivatedLineIsAccepted(): void
    {
        $this->rent->setEndYear(2026)->setEndMonth(6);
        $this->entityManager()->flush();

        $this->postAmount(['month' => 6]);

        self::assertResponseStatusCodeSame(201);
    }

    public function testLineOfAnotherProfileIsRejected(): void
    {
        $bob = $this->persist((new Profile())->setName('Bob'));

        $this->postAmount(as: $bob);

        // The fixed expense IRI doesn't resolve: lines are scoped too
        self::assertResponseStatusCodeSame(400);
    }

    public function testCorrectingAMonthOnlyChangesItsAmount(): void
    {
        $amount = $this->createAmount($this->rent, 2026, 6);

        $body = $this->request('PATCH', '/api/fixed_expense_amounts/'.$amount->getId(), ['amount' => '21.20', 'month' => 7, 'year' => 2030], self::activeProfile($this->alice));

        self::assertResponseIsSuccessful();
        self::assertSame('21.20', $body['amount']);
        self::assertSame([6 => '21.20'], $this->amountsOf($this->rent, 2026));
    }

    public function testCorrectingKeepsTheFrozenDivisor(): void
    {
        $household = $this->createHousehold($this->alice);
        $line = $this->createFixedExpense(household: $household);
        $amount = $this->createAmount($line, 2026, 1);
        $this->addMember($household, externalLabel: 'Sam');

        $body = $this->request('PATCH', '/api/fixed_expense_amounts/'.$amount->getId(), ['amount' => '600.00'], self::activeProfile($this->alice));

        self::assertResponseIsSuccessful();
        self::assertSame(1, $body['appliedDivisor']);
    }

    public function testEmptyingAMonth(): void
    {
        $amount = $this->createAmount($this->rent, 2026, 6);

        $this->request('DELETE', '/api/fixed_expense_amounts/'.$amount->getId(), headers: self::activeProfile($this->alice));

        self::assertResponseStatusCodeSame(204);
        self::assertSame([], $this->amountsOf($this->rent, 2026));
    }

    public function testAmountOfAnotherProfileIsNotFound(): void
    {
        $bob = $this->persist((new Profile())->setName('Bob'));
        $amount = $this->createAmount($this->rent, 2026, 6);

        $this->request('PATCH', '/api/fixed_expense_amounts/'.$amount->getId(), ['amount' => '1.00'], self::activeProfile($bob));

        self::assertResponseStatusCodeSame(404);
    }
}
