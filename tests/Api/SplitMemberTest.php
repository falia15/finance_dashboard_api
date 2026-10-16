<?php

namespace App\Tests\Api;

use App\Entity\Household;
use App\Entity\HouseholdMember;
use App\Entity\Income;
use App\Entity\Profile;

/**
 * Covers App\Validator\Household\ValidSplitMember through `Income` (shared by
 * `Expense` and `FixedExpense`), and App\Validator\Household\MemberHasNoEntries.
 */
final class SplitMemberTest extends ApiTestCase
{
    use HouseholdFixtures;
    use IncomeFixtures;

    private Profile $alice;
    private Household $household;
    private HouseholdMember $partner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->alice = $this->persist((new Profile())->setName('Alice'));
        $this->household = $this->createHousehold($this->alice);
        $this->partner = $this->addMember($this->household, externalLabel: 'Partner');
    }

    /**
     * @param array<string, mixed> $fields
     *
     * @return array<string, mixed>|null
     */
    private function postIncome(array $fields): ?array
    {
        return $this->request('POST', '/api/incomes', $fields + ['year' => 2026, 'month' => 9, 'type' => $this->incomeTypeIri(), 'amount' => '1000.00'], self::activeProfile($this->alice));
    }

    private static function iri(Household|HouseholdMember $entity): string
    {
        return ($entity instanceof Household ? '/api/households/' : '/api/household_members/').$entity->getId();
    }

    public function testHouseholdRowIsSplitEquallyByDefault(): void
    {
        $body = $this->postIncome(['household' => self::iri($this->household)]);

        self::assertResponseStatusCodeSame(201);
        self::assertNull($body['member'] ?? null);
    }

    public function testHouseholdRowCanBeAssignedToOneMember(): void
    {
        $body = $this->postIncome(['household' => self::iri($this->household), 'member' => self::iri($this->partner)]);

        self::assertResponseStatusCodeSame(201);
        self::assertSame(self::iri($this->partner), $body['member']);
    }

    public function testMemberRequiresAHousehold(): void
    {
        $body = $this->postIncome(['profile' => '/api/profiles/'.$this->alice->getId(), 'member' => self::iri($this->partner)]);

        self::assertResponseStatusCodeSame(422);
        self::assertContains('member', self::violationPaths($body));
    }

    public function testMemberOfAnotherHouseholdIsRejected(): void
    {
        $other = $this->createHousehold($this->alice, 'Flatshare');
        $roommate = $this->addMember($other, externalLabel: 'Roommate');

        $body = $this->postIncome(['household' => self::iri($this->household), 'member' => self::iri($roommate)]);

        self::assertResponseStatusCodeSame(422);
        self::assertContains('member', self::violationPaths($body));
    }

    public function testMemberWhoLeftCannotBePicked(): void
    {
        $former = $this->addMember($this->household, externalLabel: 'Former', leftAt: new \DateTimeImmutable('2026-06-01'));

        $body = $this->postIncome(['household' => self::iri($this->household), 'member' => self::iri($former)]);

        self::assertResponseStatusCodeSame(422);
        self::assertContains('member', self::violationPaths($body));
    }

    public function testRowKeepsItsMemberAfterTheMemberLeft(): void
    {
        $income = $this->postIncome(['household' => self::iri($this->household), 'member' => self::iri($this->partner)]);
        $this->request('PATCH', self::iri($this->partner), ['leftAt' => '2026-09-30'], self::activeProfile($this->alice));
        self::assertResponseIsSuccessful();

        $this->request('PATCH', $income['@id'], ['amount' => '1100.00'], self::activeProfile($this->alice));

        self::assertResponseIsSuccessful();
    }

    public function testAssignedMemberCannotBeDeleted(): void
    {
        $this->persist((new Income())
            ->setHousehold($this->household)
            ->setMember($this->partner)
            ->setYear(2026)
            ->setMonth(9)
            ->setType($this->incomeType())
            ->setAmount('1000.00'));

        $body = $this->request('DELETE', self::iri($this->partner), headers: self::activeProfile($this->alice));

        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('cannot be deleted', $body['violations'][0]['message'] ?? '');
    }
}
