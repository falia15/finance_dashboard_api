<?php

namespace App\Tests\Api;

use App\Entity\Income;
use App\Entity\Profile;

/**
 * Covers App\EventListener\AppliedDivisorListener through `Income`.
 */
final class AppliedDivisorTest extends ApiTestCase
{
    use HouseholdFixtures;

    private Profile $alice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->alice = $this->persist((new Profile())->setName('Alice'));
    }

    /**
     * @param array<string, mixed> $owner
     *
     * @return array<string, mixed>|null
     */
    private function postIncome(array $owner): ?array
    {
        return $this->request('POST', '/api/incomes', $owner + ['year' => 2026, 'month' => 9, 'netPay' => '1000.00'], self::activeProfile($this->alice));
    }

    public function testIndividualRowIsNotSplit(): void
    {
        $body = $this->postIncome(['profile' => '/api/profiles/'.$this->alice->getId()]);

        self::assertSame(1, $body['appliedDivisor']);
    }

    public function testHouseholdRowIsSplitBetweenActiveMembers(): void
    {
        $household = $this->createHousehold($this->alice);
        $this->addMember($household, externalLabel: 'Partner');
        $this->addMember($household, externalLabel: 'Roommate');
        $this->addMember($household, externalLabel: 'Former roommate', leftAt: new \DateTimeImmutable('2026-06-01'));

        $body = $this->postIncome(['household' => '/api/households/'.$household->getId()]);

        self::assertResponseStatusCodeSame(201);
        self::assertSame(3, $body['appliedDivisor']);
    }

    public function testDivisorIsFrozenWhenMembersChange(): void
    {
        $household = $this->createHousehold($this->alice);
        $partner = $this->addMember($household, externalLabel: 'Partner');
        $income = $this->postIncome(['household' => '/api/households/'.$household->getId()]);

        $this->request('PATCH', '/api/household_members/'.$partner->getId(), ['leftAt' => '2026-09-01'], self::activeProfile($this->alice));
        self::assertResponseIsSuccessful();
        $this->request('PATCH', $income['@id'], ['netPay' => '1200.00'], self::activeProfile($this->alice));

        $this->entityManager()->clear();
        self::assertSame(2, $this->entityManager()->find(Income::class, $income['id'])->getAppliedDivisor());
    }

    public function testMovingARowToAHouseholdRecomputesTheDivisor(): void
    {
        $household = $this->createHousehold($this->alice);
        $this->addMember($household, externalLabel: 'Partner');
        $income = $this->postIncome(['profile' => '/api/profiles/'.$this->alice->getId()]);

        $body = $this->request('PATCH', $income['@id'], ['profile' => null, 'household' => '/api/households/'.$household->getId()], self::activeProfile($this->alice));

        self::assertResponseIsSuccessful();
        self::assertSame(2, $body['appliedDivisor']);
    }
}
