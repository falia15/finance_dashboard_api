<?php

namespace App\Tests\Api;

use App\Entity\Household;
use App\Entity\HouseholdMember;
use App\Entity\Income;
use App\Entity\IncomeType;
use App\Entity\Profile;

/**
 * Covers `GET /profiles/{id}/incomes?year=` (App\Service\ProfileIncomeYearBuilder).
 */
final class ProfileIncomesTest extends ApiTestCase
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
     * @return array<string, mixed>|null
     */
    private function getYear(int $year = 2026, ?Profile $profile = null): ?array
    {
        $profile ??= $this->alice;

        return $this->request('GET', sprintf('/api/profiles/%d/incomes?year=%d', $profile->getId(), $year), headers: self::activeProfile($this->alice));
    }

    private function income(int $month, string $amount, ?IncomeType $type = null, int $year = 2026): Income
    {
        return (new Income())
            ->setYear($year)
            ->setMonth($month)
            ->setType($type ?? $this->incomeType())
            ->setAmount($amount);
    }

    private function profileIncome(int $month, string $amount, ?IncomeType $type = null, int $year = 2026): Income
    {
        return $this->persist($this->income($month, $amount, $type, $year)->setProfile($this->alice));
    }

    /** Through the API, so that AppliedDivisorListener freezes the divisor. */
    private function householdIncome(Household $household, int $month, string $amount, ?HouseholdMember $member = null): void
    {
        $this->request('POST', '/api/incomes', [
            'household' => '/api/households/'.$household->getId(),
            'member' => null === $member ? null : '/api/household_members/'.$member->getId(),
            'year' => 2026,
            'month' => $month,
            'type' => $this->incomeTypeIri(),
            'amount' => $amount,
        ], self::activeProfile($this->alice));
        self::assertResponseStatusCodeSame(201);
    }

    /**
     * Drops the JSON-LD `@type` of the nested objects.
     *
     * @param array<mixed> $data
     *
     * @return array<mixed>
     */
    private static function withoutType(array $data): array
    {
        unset($data['@type']);

        return array_map(static fn ($value) => \is_array($value) ? self::withoutType($value) : $value, $data);
    }

    /**
     * @param array<string, mixed> $body
     *
     * @return array<string, mixed>
     */
    private static function month(array $body, int $month): array
    {
        return self::withoutType($body['months'][$month - 1]);
    }

    public function testEmptyYearHasTwelveMonths(): void
    {
        $body = $this->getYear();

        self::assertResponseIsSuccessful();
        self::assertSame($this->alice->getId(), $body['profileId']);
        self::assertSame(2026, $body['year']);
        self::assertSame(range(1, 12), array_column($body['months'], 'month'));
        self::assertSame([], self::month($body, 1)['incomes']);
        self::assertSame('0.00', self::month($body, 1)['total']);
        self::assertSame(['individual' => '0.00', 'householdShare' => '0.00', 'total' => '0.00'], self::withoutType($body['totals']));
    }

    public function testSeveralTypesInTheSameMonth(): void
    {
        $mealVouchers = $this->persist((new IncomeType())->setName('Meal vouchers')->setSortOrder(2));
        $salary = $this->profileIncome(1, '2073.00');
        $vouchers = $this->profileIncome(1, '160.00', $mealVouchers);

        $january = self::month($this->getYear(), 1);

        self::assertSame([
            ['id' => $salary->getId(), 'type' => $this->incomeTypeIri(), 'amount' => '2073.00'],
            ['id' => $vouchers->getId(), 'type' => '/api/income_types/'.$mealVouchers->getId(), 'amount' => '160.00'],
        ], $january['incomes']);
        self::assertSame('2233.00', $january['total']);
    }

    public function testOtherYearsAreLeftOut(): void
    {
        $this->profileIncome(1, '2073.00');
        $this->profileIncome(1, '1900.00', year: 2025);

        $body = $this->getYear();

        self::assertSame('2073.00', $body['totals']['total']);
    }

    public function testOwnerGetsTheOddCentOfAnEqualSplit(): void
    {
        $household = $this->createHousehold($this->alice);
        $this->addMember($household, externalLabel: 'Partner');
        $this->householdIncome($household, 3, '45.01');

        $body = $this->getYear();
        $share = self::month($body, 3)['householdShares'][0];

        self::assertSame('/api/households/'.$household->getId(), $share['household']);
        self::assertSame('45.01', $share['amount']);
        self::assertSame(2, $share['appliedDivisor']);
        self::assertArrayHasKey('member', $share);
        self::assertNull($share['member']);
        self::assertSame('22.51', $share['share']);
        self::assertSame('22.51', self::month($body, 3)['total']);
    }

    public function testNonOwnerGetsTheRoundedDownShare(): void
    {
        $bob = $this->persist((new Profile())->setName('Bob'));
        $household = $this->createHousehold($bob);
        $this->addMember($household, profile: $this->alice);
        $this->householdIncome($household, 3, '45.01');

        $body = $this->getYear();

        self::assertSame('22.50', self::month($body, 3)['householdShares'][0]['share']);
    }

    public function testRowAssignedToAMember(): void
    {
        $household = $this->createHousehold($this->alice);
        $aliceMember = $household->getMembers()->first();
        $partner = $this->addMember($household, externalLabel: 'Partner');
        $this->householdIncome($household, 4, '150.00', $aliceMember);
        $this->householdIncome($household, 5, '120.00', $partner);

        $body = $this->getYear();

        self::assertSame('150.00', self::month($body, 4)['householdShares'][0]['share']);
        $partnerRow = self::month($body, 5)['householdShares'][0];
        self::assertSame('/api/household_members/'.$partner->getId(), $partnerRow['member']);
        self::assertSame('0.00', $partnerRow['share']);
        self::assertSame(['individual' => '0.00', 'householdShare' => '150.00', 'total' => '150.00'], self::withoutType($body['totals']));
    }

    public function testFrozenDivisorIsUsed(): void
    {
        $household = $this->createHousehold($this->alice);
        $partner = $this->addMember($household, externalLabel: 'Partner');
        $this->householdIncome($household, 3, '600.00');

        $this->request('PATCH', '/api/household_members/'.$partner->getId(), ['leftAt' => '2026-09-01'], self::activeProfile($this->alice));
        self::assertResponseIsSuccessful();

        self::assertSame('300.00', self::month($this->getYear(), 3)['householdShares'][0]['share']);
    }

    public function testTotalsAddIndividualIncomesAndShares(): void
    {
        $household = $this->createHousehold($this->alice);
        $this->addMember($household, externalLabel: 'Partner');
        $this->profileIncome(1, '2073.00');
        $this->profileIncome(2, '1798.85');
        $this->householdIncome($household, 1, '600.00');

        $body = $this->getYear();

        self::assertSame('2373.00', self::month($body, 1)['total']);
        self::assertSame(['individual' => '3871.85', 'householdShare' => '300.00', 'total' => '4171.85'], self::withoutType($body['totals']));
    }

    public function testHouseholdsLeftAreLeftOut(): void
    {
        $bob = $this->persist((new Profile())->setName('Bob'));
        $household = $this->createHousehold($bob);
        $this->addMember($household, profile: $this->alice, leftAt: new \DateTimeImmutable('2026-06-01'));
        $this->persist($this->income(3, '600.00')->setHousehold($household)->setAppliedDivisor(2));

        $body = $this->getYear();

        self::assertSame('0.00', $body['totals']['householdShare']);
    }

    public function testAnotherProfileIsNotFound(): void
    {
        $bob = $this->persist((new Profile())->setName('Bob'));

        $this->getYear(profile: $bob);

        self::assertResponseStatusCodeSame(404);
    }

    public function testYearIsRequired(): void
    {
        $this->request('GET', sprintf('/api/profiles/%d/incomes', $this->alice->getId()), headers: self::activeProfile($this->alice));

        self::assertResponseStatusCodeSame(422);
    }

    public function testYearMustBeValid(): void
    {
        $this->request('GET', sprintf('/api/profiles/%d/incomes?year=abc', $this->alice->getId()), headers: self::activeProfile($this->alice));

        self::assertResponseStatusCodeSame(422);
    }
}
