<?php

namespace App\Tests\Service;

use App\Entity\HouseholdMember;
use App\Entity\Income;
use App\Service\IncomeShareCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IncomeShareCalculatorTest extends TestCase
{
    private IncomeShareCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new IncomeShareCalculator();
    }

    /**
     * @return iterable<string, array{string, int, bool, string}>
     */
    public static function shares(): iterable
    {
        yield 'even split, owner' => ['600.00', 2, true, '300.00'];
        yield 'even split, other member' => ['600.00', 2, false, '300.00'];
        yield 'odd cent, owner gets it' => ['45.01', 2, true, '22.51'];
        yield 'odd cent, other member' => ['45.01', 2, false, '22.50'];
        yield 'two cents left, owner' => ['100.00', 3, true, '33.34'];
        yield 'two cents left, other member' => ['100.00', 3, false, '33.33'];
        yield 'less than a cent each' => ['0.01', 2, false, '0.00'];
        yield 'whole cent to the owner' => ['0.01', 2, true, '0.01'];
        yield 'divisor 1 keeps the amount' => ['2073.45', 1, true, '2073.45'];
        yield 'one decimal digit' => ['10.5', 2, false, '5.25'];
        yield 'no decimal digit' => ['7', 2, true, '3.50'];
        yield 'zero' => ['0.00', 2, true, '0.00'];
    }

    #[DataProvider('shares')]
    public function testShare(string $amount, int $divisor, bool $absorbsRemainder, string $expected): void
    {
        self::assertSame($expected, $this->calculator->share($amount, $divisor, $absorbsRemainder));
    }

    public function testSharesAddUpToTheAmount(): void
    {
        $owner = $this->calculator->share('100.00', 3, true);
        $other = $this->calculator->share('100.00', 3, false);

        self::assertSame('100.00', $this->calculator->sum($owner, $other, $other));
    }

    public function testDivisorMustBePositive(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->calculator->share('10.00', 0, true);
    }

    public function testInvalidAmountIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->calculator->share('1e3', 2, true);
    }

    public function testShareForMemberGetsTheWholeAmount(): void
    {
        $viewer = new HouseholdMember();
        $income = (new Income())->setAmount('150.00')->setAppliedDivisor(2)->setMember($viewer);

        self::assertSame('150.00', $this->calculator->shareFor($income, $viewer));
    }

    public function testShareForAnotherMemberIsZero(): void
    {
        $viewer = (new HouseholdMember())->setIsOwner(true);
        $income = (new Income())->setAmount('150.00')->setAppliedDivisor(2)->setMember(new HouseholdMember());

        self::assertSame('0.00', $this->calculator->shareFor($income, $viewer));
    }

    public function testShareForEqualSplitUsesTheFrozenDivisor(): void
    {
        $income = (new Income())->setAmount('45.01')->setAppliedDivisor(2);

        self::assertSame('22.51', $this->calculator->shareFor($income, (new HouseholdMember())->setIsOwner(true)));
        self::assertSame('22.50', $this->calculator->shareFor($income, new HouseholdMember()));
    }

    /**
     * @return iterable<string, array{list<string>, string}>
     */
    public static function sums(): iterable
    {
        yield 'no float error' => [['0.10', '0.20'], '0.30'];
        yield 'nothing' => [[], '0.00'];
        yield 'carry over' => [['2073.99', '0.01'], '2074.00'];
        yield 'negative' => [['10.00', '-10.01'], '-0.01'];
    }

    /**
     * @param list<string> $amounts
     */
    #[DataProvider('sums')]
    public function testSum(array $amounts, string $expected): void
    {
        self::assertSame($expected, $this->calculator->sum(...$amounts));
    }
}
