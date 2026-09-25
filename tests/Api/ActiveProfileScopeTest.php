<?php

namespace App\Tests\Api;

use App\Entity\Household;
use App\Entity\HouseholdMember;
use App\Entity\Income;
use App\Entity\Profile;
use App\Http\ActiveProfileResolver;

/**
 * Covers App\Doctrine\ActiveProfileExtension through `Income`, the other
 * scoped resources share the same logic.
 */
final class ActiveProfileScopeTest extends ApiTestCase
{
    private Profile $alice;
    private Profile $bob;

    protected function setUp(): void
    {
        parent::setUp();

        $this->alice = $this->persist((new Profile())->setName('Alice'));
        $this->bob = $this->persist((new Profile())->setName('Bob'));
    }

    private function createIncome(string $label, ?Profile $profile = null, ?Household $household = null): Income
    {
        return $this->persist((new Income())
            ->setProfile($profile)
            ->setHousehold($household)
            ->setYear(2026)
            ->setMonth(9)
            ->setLabel($label)
            ->setNetPay('1000.00'));
    }

    private function createHousehold(Profile $member, ?\DateTimeImmutable $leftAt = null): Household
    {
        $household = $this->persist((new Household())->setName('Home'));
        $this->persist((new HouseholdMember())
            ->setHousehold($household)
            ->setProfile($member)
            ->setIsOwner(true)
            ->setJoinedAt(new \DateTimeImmutable('2026-01-01'))
            ->setLeftAt($leftAt));

        return $household;
    }

    /**
     * @return list<string> labels of the incomes visible to the given profile
     */
    private function visibleIncomeLabels(Profile $activeProfile): array
    {
        $body = $this->request('GET', '/api/incomes', headers: [ActiveProfileResolver::HEADER => (string) $activeProfile->getId()]);
        self::assertResponseIsSuccessful();

        $labels = array_column($body['member'], 'label');
        sort($labels);

        return $labels;
    }

    public function testMissingHeaderIsRejected(): void
    {
        $this->request('GET', '/api/incomes');

        self::assertResponseStatusCodeSame(400);
    }

    public function testNonNumericHeaderIsRejected(): void
    {
        $this->request('GET', '/api/incomes', headers: [ActiveProfileResolver::HEADER => 'abc']);

        self::assertResponseStatusCodeSame(400);
    }

    public function testUnknownProfileHeaderIsRejected(): void
    {
        $this->request('GET', '/api/incomes', headers: [ActiveProfileResolver::HEADER => '999999']);

        self::assertResponseStatusCodeSame(400);
    }

    public function testCollectionOnlyContainsTheActiveProfileRows(): void
    {
        $this->createIncome('Alice salary', profile: $this->alice);
        $this->createIncome('Bob salary', profile: $this->bob);

        self::assertSame(['Alice salary'], $this->visibleIncomeLabels($this->alice));
        self::assertSame(['Bob salary'], $this->visibleIncomeLabels($this->bob));
    }

    public function testItemOfAnotherProfileIsNotFound(): void
    {
        $bobIncome = $this->createIncome('Bob salary', profile: $this->bob);
        $headers = [ActiveProfileResolver::HEADER => (string) $this->alice->getId()];

        $this->request('GET', '/api/incomes/'.$bobIncome->getId(), headers: $headers);
        self::assertResponseStatusCodeSame(404);

        $this->request('PATCH', '/api/incomes/'.$bobIncome->getId(), ['label' => 'Hijacked'], $headers);
        self::assertResponseStatusCodeSame(404);

        $this->request('DELETE', '/api/incomes/'.$bobIncome->getId(), headers: $headers);
        self::assertResponseStatusCodeSame(404);
    }

    public function testHouseholdRowsAreVisibleToActiveMembersOnly(): void
    {
        $household = $this->createHousehold($this->alice);
        $this->createIncome('Shared', household: $household);

        self::assertSame(['Shared'], $this->visibleIncomeLabels($this->alice));
        self::assertSame([], $this->visibleIncomeLabels($this->bob));
    }

    public function testHouseholdRowsAreHiddenOnceTheMemberLeft(): void
    {
        $household = $this->createHousehold($this->alice, leftAt: new \DateTimeImmutable('2026-06-01'));
        $this->createIncome('Shared', household: $household);

        self::assertSame([], $this->visibleIncomeLabels($this->alice));
    }
}
