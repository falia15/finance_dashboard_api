<?php

namespace App\Tests\Api;

use App\Entity\FixedExpense;
use App\Entity\Household;
use App\Entity\HouseholdMember;
use App\Entity\Profile;

/**
 * Covers `GET /profiles/{id}/fixed_expenses?year=`
 * (App\Service\ProfileFixedExpenseYearBuilder).
 */
final class ProfileFixedExpensesTest extends ApiTestCase
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
     * @return array<string, mixed>|null
     */
    private function getYear(int $year = 2026, ?Profile $profile = null): ?array
    {
        $profile ??= $this->alice;

        return $this->request('GET', sprintf('/api/profiles/%d/fixed_expenses?year=%d', $profile->getId(), $year), headers: self::activeProfile($this->alice));
    }

    /**
     * @param array<int, string>|string $amounts by month, or one amount for the 12 months
     */
    private function line(string $label, array|string $amounts, ?Household $household = null, ?HouseholdMember $member = null): FixedExpense
    {
        $line = $this->createFixedExpense(profile: null === $household ? $this->alice : null, household: $household, label: $label);
        if (null !== $member) {
            $line->setHouseholdMember($member);
            $this->entityManager()->flush();
        }

        foreach (\is_string($amounts) ? array_fill(1, 12, $amounts) : $amounts as $month => $amount) {
            $this->createAmount($line, 2026, $month, $amount);
        }

        return $line;
    }

    /**
     * The "CHARGES FIXES" tabs of the example spreadsheet: Alice's own
     * lines, and the couple's lines shared with Sam (external member).
     */
    public function testSpreadsheetExample(): void
    {
        $this->line('Bank', '0.00');
        $this->line('Donations', '15.00');
        $this->line('Car insurance', '70.00');

        $household = $this->createHousehold($this->alice);
        $sam = $this->addMember($household, externalLabel: 'Sam');
        $this->line('Phone + Internet', '34.99', $household);
        $this->line('Bank (joint account)', [8 => '21.20'] + array_fill(1, 12, '12.25'), $household);
        $this->line('Rent', '560.00', $household);
        $this->line('Spotify', '21.24', $household);
        $this->line('Electricity', '300.00', $household);
        $this->line('Water', [6 => '175.22'], $household, $sam);

        $body = $this->getYear();

        self::assertResponseIsSuccessful();
        self::assertSame(['Bank', 'Donations', 'Car insurance'], array_column($body['lines'], 'label'));
        self::assertSame('840.00', $body['lines'][2]['total']);

        $grid = $body['households'][0];
        self::assertSame('/api/households/'.$household->getId(), $grid['household']);
        self::assertSame(['month' => 1, 'total' => '928.48', 'share' => '464.25'], array_diff_key($grid['months'][0], ['@type' => true]));
        self::assertSame(['total' => '11325.93', 'share' => '5575.47'], array_diff_key($grid['totals'], ['@type' => true]));

        $water = $grid['lines'][5];
        self::assertSame('/api/household_members/'.$sam->getId(), $water['householdMember']);
        self::assertSame([['month' => 6, 'amount' => '175.22', 'appliedDivisor' => 2, 'share' => '0.00']], array_map(static fn (array $share) => array_diff_key($share, ['@type' => true, 'id' => true]), $water['amounts']));
        self::assertSame(['175.22', '0.00'], [$water['total'], $water['shareTotal']]);

        self::assertSame(['individual' => '85.00', 'householdShare' => '464.25', 'total' => '549.25'], array_diff_key($body['months'][0], ['@type' => true, 'month' => true]));
        self::assertSame(['individual' => '1020.00', 'householdShare' => '5575.47', 'total' => '6595.47'], array_diff_key($body['totals'], ['@type' => true]));
    }

    public function testEmptyLineIsListedWithoutAmounts(): void
    {
        $this->createFixedExpense(profile: $this->alice, label: 'Water');

        $body = $this->getYear();

        self::assertSame([], $body['lines'][0]['amounts']);
        self::assertSame('0.00', $body['lines'][0]['total']);
        self::assertNull($body['lines'][0]['endYear']);
        self::assertCount(12, $body['months']);
    }

    public function testLinesAreShownWithinTheirActivePeriod(): void
    {
        $this->createFixedExpense(profile: $this->alice, label: 'Started later', startYear: 2027);
        $ended = $this->createFixedExpense(profile: $this->alice, label: 'Ended this year');
        $ended->setEndYear(2026)->setEndMonth(9);
        $earlier = $this->createFixedExpense(profile: $this->alice, label: 'Ended last year', startYear: 2025);
        $earlier->setEndYear(2025)->setEndMonth(12);
        $this->entityManager()->flush();

        $body = $this->getYear();

        self::assertSame(['Ended this year'], array_column($body['lines'], 'label'));
        self::assertSame([2026, 9], [$body['lines'][0]['endYear'], $body['lines'][0]['endMonth']]);
    }

    public function testOnlyAmountsOfTheYearAreRead(): void
    {
        $line = $this->line('Rent', [1 => '560.00']);
        $this->createAmount($line, 2027, 1, '600.00');

        $body = $this->getYear();

        self::assertSame(['560.00'], array_column($body['lines'][0]['amounts'], 'amount'));
    }

    public function testHouseholdsTheProfileLeftAreNotListed(): void
    {
        $bob = $this->persist((new Profile())->setName('Bob'));
        $household = $this->createHousehold($bob);
        $this->addMember($household, profile: $this->alice, leftAt: new \DateTimeImmutable('2026-06-01'));
        $this->line('Rent', '560.00', $household);

        $body = $this->getYear();

        self::assertSame([], $body['households']);
        self::assertSame('0.00', $body['totals']['total']);
    }

    public function testEmptyHouseholdIsListed(): void
    {
        $this->createHousehold($this->alice);

        $body = $this->getYear();

        self::assertSame([], $body['households'][0]['lines']);
        self::assertSame('0.00', $body['households'][0]['totals']['total']);
    }

    public function testAnotherProfileIsNotFound(): void
    {
        $bob = $this->persist((new Profile())->setName('Bob'));

        $this->getYear(profile: $bob);

        self::assertResponseStatusCodeSame(404);
    }

    public function testYearIsRequired(): void
    {
        $this->request('GET', sprintf('/api/profiles/%d/fixed_expenses', $this->alice->getId()), headers: self::activeProfile($this->alice));

        self::assertResponseStatusCodeSame(422);
    }
}
