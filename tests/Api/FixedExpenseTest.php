<?php

namespace App\Tests\Api;

use App\Entity\FixedExpense;
use App\Entity\FixedExpenseAmount;
use App\Entity\Profile;

/**
 * Covers `/fixed_expenses`: creation with an initial amount, sort order,
 * deactivation / reactivation, deletion and validation.
 */
final class FixedExpenseTest extends ApiTestCase
{
    use FixedExpenseFixtures;
    use HouseholdFixtures;

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
    private function postFixedExpense(array $fields = []): ?array
    {
        return $this->request('POST', '/api/fixed_expenses', $fields + [
            'label' => 'Rent',
            'profile' => '/api/profiles/'.$this->alice->getId(),
            'startYear' => 2026,
        ], self::activeProfile($this->alice));
    }

    /**
     * @param array<string, mixed> $fields
     *
     * @return array<string, mixed>|null
     */
    private function patchFixedExpense(FixedExpense $fixedExpense, array $fields): ?array
    {
        return $this->request('PATCH', '/api/fixed_expenses/'.$fixedExpense->getId(), $fields, self::activeProfile($this->alice));
    }

    private function find(int $id): ?FixedExpense
    {
        $this->entityManager()->clear();

        return $this->entityManager()->find(FixedExpense::class, $id);
    }

    public function testLineWithoutInitialAmountHasNoAmount(): void
    {
        $body = $this->postFixedExpense();

        self::assertResponseStatusCodeSame(201);
        self::assertSame([], $this->amountsOf($this->find($body['id']), 2026));
        self::assertArrayNotHasKey('initialAmount', $body);
    }

    public function testInitialAmountForOneMonth(): void
    {
        $body = $this->postFixedExpense(['initialAmount' => ['year' => 2026, 'month' => 6, 'amount' => '175.22']]);

        self::assertResponseStatusCodeSame(201);
        self::assertSame([6 => '175.22'], $this->amountsOf($this->find($body['id']), 2026));
    }

    public function testInitialAmountForTheWholeYear(): void
    {
        $body = $this->postFixedExpense(['initialAmount' => ['year' => 2026, 'month' => null, 'amount' => '560.00']]);

        self::assertResponseStatusCodeSame(201);
        self::assertSame(array_fill(1, 12, '560.00'), $this->amountsOf($this->find($body['id']), 2026));
    }

    public function testInitialAmountsOfAHouseholdLineAreSplit(): void
    {
        $household = $this->createHousehold($this->alice);
        $this->addMember($household, externalLabel: 'Sam');

        $body = $this->postFixedExpense([
            'profile' => null,
            'household' => '/api/households/'.$household->getId(),
            'initialAmount' => ['year' => 2026, 'month' => 1, 'amount' => '560.00'],
        ]);

        self::assertResponseStatusCodeSame(201);
        $amount = $this->entityManager()->getRepository(FixedExpenseAmount::class)->findOneBy(['fixedExpense' => $body['id']]);
        self::assertSame(2, $amount->getAppliedDivisor());
    }

    public function testInitialAmountBeforeTheStartYearIsRejected(): void
    {
        $body = $this->postFixedExpense(['initialAmount' => ['year' => 2025, 'month' => 1, 'amount' => '10.00']]);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(['initialAmount.year'], self::violationPaths($body));
    }

    public function testNegativeInitialAmountIsRejected(): void
    {
        $body = $this->postFixedExpense(['initialAmount' => ['year' => 2026, 'month' => 1, 'amount' => '-1.00']]);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(['initialAmount.amount'], self::violationPaths($body));
    }

    public function testNewLineGoesAfterTheLinesOfTheSameOwner(): void
    {
        $this->createFixedExpense(profile: $this->alice, sortOrder: 4);
        $household = $this->createHousehold($this->alice);
        $this->createFixedExpense(household: $household, sortOrder: 9);

        $body = $this->postFixedExpense(['sortOrder' => 0]);

        self::assertResponseStatusCodeSame(201);
        self::assertSame(5, $body['sortOrder']);
    }

    public function testBlankLabelIsRejected(): void
    {
        $body = $this->postFixedExpense(['label' => ' ']);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(['label'], self::violationPaths($body));
    }

    public function testStartYearIsRequired(): void
    {
        $body = $this->postFixedExpense(['startYear' => null]);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(['startYear'], self::violationPaths($body));
    }

    public function testEndCantBeSetOnCreation(): void
    {
        $body = $this->postFixedExpense(['endYear' => 2026, 'endMonth' => 3]);

        self::assertResponseStatusCodeSame(201);
        self::assertNull($body['endYear'] ?? null);
    }

    public function testStartYearCantBeChanged(): void
    {
        $fixedExpense = $this->createFixedExpense(profile: $this->alice);

        $this->patchFixedExpense($fixedExpense, ['startYear' => 2020, 'label' => 'New rent']);

        self::assertResponseIsSuccessful();
        $fixedExpense = $this->find($fixedExpense->getId());
        self::assertSame(2026, $fixedExpense->getStartYear());
        self::assertSame('New rent', $fixedExpense->getLabel());
    }

    public function testDeactivatingDeletesTheLaterAmounts(): void
    {
        $fixedExpense = $this->createFixedExpense(profile: $this->alice);
        foreach ([8, 9, 10, 12] as $month) {
            $this->createAmount($fixedExpense, 2026, $month);
        }
        $this->createAmount($fixedExpense, 2027, 1);

        $this->patchFixedExpense($fixedExpense, ['endYear' => 2026, 'endMonth' => 9]);

        self::assertResponseIsSuccessful();
        self::assertSame([8 => '560.00', 9 => '560.00'], $this->amountsOf($fixedExpense, 2026));
        self::assertSame([], $this->amountsOf($fixedExpense, 2027));
    }

    public function testReactivatingClearsTheEnd(): void
    {
        $fixedExpense = $this->createFixedExpense(profile: $this->alice);
        $this->patchFixedExpense($fixedExpense, ['endYear' => 2026, 'endMonth' => 9]);

        $this->patchFixedExpense($fixedExpense, ['endYear' => null, 'endMonth' => null]);

        self::assertResponseIsSuccessful();
        self::assertFalse($this->find($fixedExpense->getId())->isDeactivated());
    }

    public function testIncompleteEndIsRejected(): void
    {
        $fixedExpense = $this->createFixedExpense(profile: $this->alice);

        $body = $this->patchFixedExpense($fixedExpense, ['endYear' => 2026]);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(['endMonth'], self::violationPaths($body));
    }

    public function testEndBeforeTheStartIsRejected(): void
    {
        $fixedExpense = $this->createFixedExpense(profile: $this->alice);
        $this->createAmount($fixedExpense, 2026, 1);

        $body = $this->patchFixedExpense($fixedExpense, ['endYear' => 2025, 'endMonth' => 12]);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(['endYear'], self::violationPaths($body));
        self::assertSame([1 => '560.00'], $this->amountsOf($fixedExpense, 2026));
    }

    public function testDeletingALineDeletesItsAmounts(): void
    {
        $fixedExpense = $this->createFixedExpense(profile: $this->alice);
        $this->createAmount($fixedExpense, 2026, 1);
        $this->createAmount($fixedExpense, 2027, 1);

        $this->request('DELETE', '/api/fixed_expenses/'.$fixedExpense->getId(), headers: self::activeProfile($this->alice));

        self::assertResponseStatusCodeSame(204);
        self::assertSame(0, $this->entityManager()->getRepository(FixedExpenseAmount::class)->count(['fixedExpense' => $fixedExpense->getId()]));
    }
}
