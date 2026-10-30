<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Entity\Trait\TimestampableTrait;
use App\Repository\FixedExpenseAmountRepository;
use App\Validator\FixedExpense\InFixedExpensePeriod;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * One filled month of a fixed expense — an empty month has no row. POST
 * fills a month, PATCH corrects its amount (the month itself can't move),
 * DELETE empties it. No collection: the year grid is a read model.
 *
 * Only reachable through a fixed expense the active profile can see: a
 * foreign `fixedExpense` IRI doesn't resolve (400), a foreign row is a 404
 * (ActiveProfileExtension).
 */
#[ORM\Entity(repositoryClass: FixedExpenseAmountRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'fixed_expense_amount')]
#[ORM\UniqueConstraint(
    name: 'uniq_fixed_expense_amount_period',
    columns: ['fixed_expense_id', 'year', 'month'
])]
#[UniqueEntity(fields: ['fixedExpense', 'year', 'month'], message: 'This fixed expense already has an amount for this month.', errorPath: 'month')]
#[InFixedExpensePeriod]
#[ApiResource(operations: [
    new Get(),
    new Post(),
    new Patch(denormalizationContext: [AbstractNormalizer::IGNORED_ATTRIBUTES => ['fixedExpense', 'year', 'month']]),
    new Delete(),
])]
class FixedExpenseAmount
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: FixedExpense::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?FixedExpense $fixedExpense = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Range(min: 2000, max: 2100)]
    private ?int $year = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Range(min: 1, max: 12)]
    private ?int $month = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
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
