<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Entity\Trait\TimestampableTrait;
use App\Repository\IncomeRepository;
use App\Validator\Common\ExactlyOneOf;
use App\Validator\Household\ValidRowOwnerMember;
use App\Validator\Profile\InActiveProfileScope;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: IncomeRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[InActiveProfileScope]
#[ExactlyOneOf(first: 'profile', second: 'household')]
#[ValidRowOwnerMember]
// One row per period and type for an owner; each check is skipped when its owner is null
#[UniqueEntity(fields: ['profile', 'year', 'month', 'type'], message: 'This profile already has an income of this type for this month.', errorPath: 'type')]
#[UniqueEntity(fields: ['household', 'year', 'month', 'type'], message: 'This household already has an income of this type for this month.', errorPath: 'type')]
#[ORM\Table(name: 'income')]
#[ORM\UniqueConstraint(name: 'uniq_income_profile_period_type', columns: ['profile_id', 'year', 'month', 'type_id'])]
#[ORM\UniqueConstraint(name: 'uniq_income_household_period_type', columns: ['household_id', 'year', 'month', 'type_id'])]
#[ApiResource(operations: [
    new GetCollection(),
    new GetCollection(
        uriTemplate: '/households/{householdId}/incomes',
        uriVariables: ['householdId' => new Link(fromClass: Household::class, toProperty: 'household')],
    ),
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
class Income implements HouseholdRowInterface
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
    private ?HouseholdMember $householdMember = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Range(min: 2000, max: 2100)]
    private ?int $year = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Range(min: 1, max: 12)]
    private ?int $month = null;

    #[ORM\ManyToOne(targetEntity: IncomeType::class)]
    #[ORM\JoinColumn(name: 'type_id', nullable: false)]
    #[Assert\NotNull]
    private ?IncomeType $type = null;

    /** Never negative: a correction is entered by lowering the month's amount. */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
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

    public function getHouseholdMember(): ?HouseholdMember
    {
        return $this->householdMember;
    }

    public function setHouseholdMember(?HouseholdMember $householdMember): static
    {
        $this->householdMember = $householdMember;

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
