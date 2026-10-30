<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use App\Entity\Trait\TimestampableTrait;
use App\Repository\FixedExpenseAmountRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * No GetCollection/Post/Delete — rows are only ever bulk-created via the
 * `fixed_expenses` duplication flow (not implemented yet) and corrected
 * one month at a time here.
 */
#[ORM\Entity(repositoryClass: FixedExpenseAmountRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'fixed_expense_amount')]
#[ORM\UniqueConstraint(
    name: 'uniq_fixed_expense_amount_period', 
    columns: ['fixed_expense_id', 'year', 'month'
])]
#[ApiResource(operations: [
    new Get(),
    new Patch(),
])]
class FixedExpenseAmount
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: FixedExpense::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?FixedExpense $fixedExpense = null;

    #[ORM\Column]
    private ?int $year = null;

    #[ORM\Column]
    private ?int $month = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount = null;

    /** Active member count of the fixed expense's household when the month was created — see AppliedDivisorListener. */
    #[ORM\Column]
    #[ApiProperty(writable: false)]
    private ?int $appliedDivisor = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFixedExpense(): ?FixedExpense
    {
        return $this->fixedExpense;
    }

    public function setFixedExpense(?FixedExpense $fixedExpense): static
    {
        $this->fixedExpense = $fixedExpense;

        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(int $year): static
    {
        $this->year = $year;

        return $this;
    }

    public function getMonth(): ?int
    {
        return $this->month;
    }

    public function setMonth(int $month): static
    {
        $this->month = $month;

        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getAppliedDivisor(): ?int
    {
        return $this->appliedDivisor;
    }

    public function setAppliedDivisor(int $appliedDivisor): static
    {
        $this->appliedDivisor = $appliedDivisor;

        return $this;
    }
}
