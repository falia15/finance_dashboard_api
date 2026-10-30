<?php

namespace App\Tests\Api;

use App\Entity\Household;
use App\Entity\Income;
use App\Entity\IncomeType;
use App\Entity\Profile;

/**
 * Covers the validation of `Income` and `GET /households/{id}/incomes`.
 */
final class IncomeTest extends ApiTestCase
{
    use HouseholdFixtures;
    use IncomeFixtures;

    private Profile $alice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->alice = $this->persist((new Profile())->setName('Alice'));
    }

    /**
     * @param array<string, mixed> $fields
     *
     * @return array<string, mixed>|null
     */
    private function postIncome(array $fields): ?array
    {
        return $this->request('POST', '/api/incomes', $fields + [
            'profile' => '/api/profiles/'.$this->alice->getId(),
            'year' => 2026,
            'month' => 9,
            'type' => $this->incomeTypeIri(),
            'amount' => '1000.00',
        ], self::activeProfile($this->alice));
    }

    private function createIncome(Household $household, int $year, string $amount = '600.00'): Income
    {
        return $this->persist((new Income())
            ->setHousehold($household)
            ->setYear($year)
            ->setMonth(3)
            ->setType($this->incomeType())
            ->setAmount($amount));
    }

    public function testMonthOutOfRangeIsRejected(): void
    {
        $body = $this->postIncome(['month' => 13]);

        self::assertResponseStatusCodeSame(422);
        self::assertContains('month', self::violationPaths($body));
    }

    public function testNegativeAmountIsRejected(): void
    {
        $body = $this->postIncome(['amount' => '-10.00']);

        self::assertResponseStatusCodeSame(422);
        self::assertContains('amount', self::violationPaths($body));
    }

    public function testTypeIsRequired(): void
    {
        $body = $this->postIncome(['type' => null]);

        self::assertResponseStatusCodeSame(422);
        self::assertContains('type', self::violationPaths($body));
    }

    public function testDuplicateIncomeIsAValidationError(): void
    {
        $this->postIncome([]);
        self::assertResponseStatusCodeSame(201);

        $body = $this->postIncome(['amount' => '1200.00']);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(['type'], self::violationPaths($body));
    }

    public function testSameMonthWithAnotherTypeIsAllowed(): void
    {
        $mealVouchers = $this->persist((new IncomeType())->setName('Meal vouchers')->setSortOrder(2));

        $this->postIncome([]);
        self::assertResponseStatusCodeSame(201);
        $this->postIncome(['type' => '/api/income_types/'.$mealVouchers->getId()]);
        self::assertResponseStatusCodeSame(201);
    }

    public function testProfileAndHouseholdRowsDoNotConflict(): void
    {
        $household = $this->createHousehold($this->alice);

        $this->postIncome([]);
        self::assertResponseStatusCodeSame(201);
        $this->postIncome(['profile' => null, 'household' => '/api/households/'.$household->getId()]);
        self::assertResponseStatusCodeSame(201);
    }

    public function testEditingARowKeepsItsOwnPeriod(): void
    {
        $income = $this->postIncome([]);

        $this->request('PATCH', $income['@id'], ['amount' => '1100.00'], self::activeProfile($this->alice));

        self::assertResponseIsSuccessful();
    }

    public function testHouseholdIncomesOfTheYear(): void
    {
        $household = $this->createHousehold($this->alice);
        $other = $this->createHousehold($this->alice, 'Flatshare');
        $income = $this->createIncome($household, 2026);
        $this->createIncome($household, 2025);
        $this->createIncome($other, 2026);

        $body = $this->request('GET', '/api/households/'.$household->getId().'/incomes?year=2026', headers: self::activeProfile($this->alice));

        self::assertResponseIsSuccessful();
        self::assertSame([$income->getId()], array_column($body['member'], 'id'));
    }

    public function testNonMemberSeesNoHouseholdIncome(): void
    {
        $household = $this->createHousehold($this->alice);
        $this->createIncome($household, 2026);
        $bob = $this->persist((new Profile())->setName('Bob'));

        $body = $this->request('GET', '/api/households/'.$household->getId().'/incomes?year=2026', headers: self::activeProfile($bob));

        self::assertResponseIsSuccessful();
        self::assertSame(0, $body['totalItems']);
    }
}
