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
use App\Validator\InActiveProfileScope;
use App\Validator\ValidSplitMember;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IncomeRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[InActiveProfileScope]
#[ExactlyOneOf(first: 'profile', second: 'household')]
#[ValidSplitMember]
#[ORM\Table(name: 'income')]
#[ORM\UniqueConstraint(name: 'uniq_income_profile_period_type', columns: ['profile_id', 'year', 'month', 'type_id'])]
#[ORM\UniqueConstraint(name: 'uniq_income_household_period_type', columns: ['household_id', 'year', 'month', 'type_id'])]
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
    'type' => 'exact',
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

    /** Household member receiving the whole income; null = split equally between members. */
    #[ORM\ManyToOne(targetEntity: HouseholdMember::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?HouseholdMember $member = null;

    #[ORM\Column]
    private ?int $year = null;

    #[ORM\Column]
    private ?int $month = null;

    #[ORM\ManyToOne(targetEntity: IncomeType::class)]
    #[ORM\JoinColumn(name: 'type_id', nullable: false)]
    private ?IncomeType $type = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount = null;

    /** Active member count of the household when the row was entered (1 if individual) — see AppliedDivisorListener. */
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

    public function getMember(): ?HouseholdMember
    {
        return $this->member;
    }

    public function setMember(?HouseholdMember $member): static
    {
        $this->member = $member;

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

    public function getType(): ?IncomeType
    {
        return $this->type;
    }

    public function setType(?IncomeType $type): static
    {
        $this->type = $type;

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
