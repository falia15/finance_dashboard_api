<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Entity\Trait\TimestampableTrait;
use App\Repository\IncomeRepository;
use App\Validator\ExactlyOneOf;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IncomeRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ExactlyOneOf(first: 'profile', second: 'household')]
#[ORM\Table(name: 'income')]
#[ORM\UniqueConstraint(name: 'uniq_income_profile_period_label', columns: ['profile_id', 'year', 'month', 'label'])]
#[ORM\UniqueConstraint(name: 'uniq_income_household_period_label', columns: ['household_id', 'year', 'month', 'label'])]
#[ApiResource(operations: [
    new GetCollection(),
    new Get(),
    new Post(),
    new Patch(),
    new Delete(),
])]
#[ApiFilter(SearchFilter::class, properties: [
    'profile' => 'exact',
    'household' => 'exact',
    'year' => 'exact',
    'month' => 'exact',
])]
class Income
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Profile::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Profile $profile = null;

    #[ORM\ManyToOne(targetEntity: Household::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Household $household = null;

    #[ORM\Column]
    private ?int $year = null;

    #[ORM\Column]
    private ?int $month = null;

    #[ORM\Column(length: 255, options: ['default' => 'Salary'])]
    private string $label = 'Salary';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $netPay = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $grossPay = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $taxableIncome = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $withholdingTax = null;

    /** Computed from the household's active member count at creation — not implemented yet, always defaults to 1 for now. */
    #[ORM\Column(options: ['default' => 1])]
    #[ApiProperty(writable: false)]
    private int $appliedDivisor = 1;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProfile(): ?Profile
    {
        return $this->profile;
    }

    public function setProfile(?Profile $profile): static
    {
        $this->profile = $profile;

        return $this;
    }

    public function getHousehold(): ?Household
    {
        return $this->household;
    }

    public function setHousehold(?Household $household): static
    {
        $this->household = $household;

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

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getNetPay(): ?string
    {
        return $this->netPay;
    }

    public function setNetPay(string $netPay): static
    {
        $this->netPay = $netPay;

        return $this;
    }

    public function getGrossPay(): ?string
    {
        return $this->grossPay;
    }

    public function setGrossPay(?string $grossPay): static
    {
        $this->grossPay = $grossPay;

        return $this;
    }

    public function getTaxableIncome(): ?string
    {
        return $this->taxableIncome;
    }

    public function setTaxableIncome(?string $taxableIncome): static
    {
        $this->taxableIncome = $taxableIncome;

        return $this;
    }

    public function getWithholdingTax(): ?string
    {
        return $this->withholdingTax;
    }

    public function setWithholdingTax(?string $withholdingTax): static
    {
        $this->withholdingTax = $withholdingTax;

        return $this;
    }

    public function getAppliedDivisor(): int
    {
        return $this->appliedDivisor;
    }

    public function setAppliedDivisor(int $appliedDivisor): static
    {
        $this->appliedDivisor = $appliedDivisor;

        return $this;
    }
}
