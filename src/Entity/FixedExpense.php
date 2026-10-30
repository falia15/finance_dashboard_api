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
use App\Dto\FixedExpenseApplyToYearInput;
use App\Dto\FixedExpenseApplyToYearOutput;
use App\Dto\FixedExpenseInitialAmount;
use App\Entity\Trait\TimestampableTrait;
use App\Repository\FixedExpenseRepository;
use App\State\FixedExpenseApplyToYearProcessor;
use App\State\FixedExpenseCreateProcessor;
use App\State\FixedExpenseUpdateProcessor;
use App\Validator\Common\ExactlyOneOf;
use App\Validator\FixedExpense\ValidFixedExpensePeriod;
use App\Validator\Household\ValidRowOwnerMember;
use App\Validator\Profile\InActiveProfileScope;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A permanent fixed expense line, shown every year from `startYear` until
 * it is deactivated (`endYear`/`endMonth`, the last month it covers). Its
 * monthly amounts are FixedExpenseAmount rows, one per filled month.
 *
 * - POST may fill the first month(s) through `initialAmount`, and appends
 *   the line at the end of its owner's list.
 * - PATCH can't move `startYear`; setting the end deletes the amounts
 *   after it, clearing it reactivates the line.
 * - DELETE removes every amount of the line (ON DELETE CASCADE).
 * - POST /fixed_expenses/{id}/apply_to_year copies one month's amount to
 *   the whole year (FixedExpenseApplyToYearProcessor).
 */
#[ORM\Entity(repositoryClass: FixedExpenseRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[InActiveProfileScope]
#[ExactlyOneOf(first: 'profile', second: 'household')]
#[ValidRowOwnerMember]
#[ValidFixedExpensePeriod]
#[ApiResource(operations: [
    new GetCollection(order: ['sortOrder' => 'ASC', 'id' => 'ASC']),
    new Get(),
    new Post(
        processor: FixedExpenseCreateProcessor::class,
        denormalizationContext: [AbstractNormalizer::IGNORED_ATTRIBUTES => ['endYear', 'endMonth']],
    ),
    new Patch(
        processor: FixedExpenseUpdateProcessor::class,
        denormalizationContext: [AbstractNormalizer::IGNORED_ATTRIBUTES => ['startYear', 'initialAmount']],
    ),
    new Delete(),
    new Post(
        uriTemplate: '/fixed_expenses/{id}/apply_to_year',
        status: 200,
        input: FixedExpenseApplyToYearInput::class,
        output: FixedExpenseApplyToYearOutput::class,
        // Loads the line (scoped to the active profile) despite the POST, 404 if not found
        read: true,
        throwOnNotFound: true,
        processor: FixedExpenseApplyToYearProcessor::class,
    ),
])]
#[ApiFilter(SearchFilter::class, properties: [
    'profile' => 'exact',
    'household' => 'exact',
])]
class FixedExpense implements HouseholdRowInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(normalizer: 'trim')]
    #[Assert\Length(max: 255)]
    private ?string $label = null;

    #[ORM\ManyToOne(targetEntity: Profile::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Profile $profile = null;

    #[ORM\ManyToOne(targetEntity: Household::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Household $household = null;

    /** Household member paying the whole fixed expense; null = split equally between members. */
    #[ORM\ManyToOne(targetEntity: HouseholdMember::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?HouseholdMember $householdMember = null;

    /** Set by FixedExpenseCreateProcessor: after the other lines of the same owner. */
    #[ORM\Column]
    #[ApiProperty(writable: false)]
    private ?int $sortOrder = null;

    /** First year the line is shown for — write-once. */
    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Range(min: 2000, max: 2100)]
    private ?int $startYear = null;

    /** Last month covered once deactivated, with `endMonth` — both null while active. */
    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 2000, max: 2100)]
    private ?int $endYear = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 1, max: 12)]
    private ?int $endMonth = null;

    /** Write-only, POST only: amount(s) created with the line by FixedExpenseCreateProcessor. */
    #[ApiProperty(readable: false)]
    #[Assert\Valid]
    private ?FixedExpenseInitialAmount $initialAmount = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
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

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    public function getStartYear(): ?int
    {
        return $this->startYear;
    }

    public function setStartYear(int $startYear): static
    {
        $this->startYear = $startYear;

        return $this;
    }

    public function getEndYear(): ?int
    {
        return $this->endYear;
    }

    public function setEndYear(?int $endYear): static
    {
        $this->endYear = $endYear;

        return $this;
    }

    public function getEndMonth(): ?int
    {
        return $this->endMonth;
    }

    public function setEndMonth(?int $endMonth): static
    {
        $this->endMonth = $endMonth;

        return $this;
    }

    public function getInitialAmount(): ?FixedExpenseInitialAmount
    {
        return $this->initialAmount;
    }

    public function setInitialAmount(?FixedExpenseInitialAmount $initialAmount): static
    {
        $this->initialAmount = $initialAmount;

        return $this;
    }

    public function isDeactivated(): bool
    {
        return null !== $this->endYear && null !== $this->endMonth;
    }

    /**
     * True when the given month is inside the line's active period: not
     * before `startYear`, not after the end of a deactivated line.
     */
    public function covers(int $year, int $month): bool
    {
        if ($year < $this->startYear) {
            return false;
        }

        return !$this->isDeactivated() || $year * 12 + $month <= $this->endYear * 12 + $this->endMonth;
    }
}
