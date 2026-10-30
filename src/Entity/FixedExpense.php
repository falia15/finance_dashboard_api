<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Entity\Trait\TimestampableTrait;
use App\Repository\FixedExpenseRepository;
use App\Validator\Common\ExactlyOneOf;
use App\Validator\Household\ValidRowOwnerMember;
use App\Validator\Profile\InActiveProfileScope;
use Doctrine\ORM\Mapping as ORM;

/**
 * `duplicateAmount` (bulk-create the 12 monthly `FixedExpenseAmount` rows
 * on creation) is a state-processor concern, not implemented yet — Post
 * here only creates the bare `FixedExpense` line.
 */
#[ORM\Entity(repositoryClass: FixedExpenseRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[InActiveProfileScope]
#[ExactlyOneOf(first: 'profile', second: 'household')]
#[ValidRowOwnerMember]
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
])]
class FixedExpense implements HouseholdRowInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
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

    #[ORM\Column]
    private ?int $sortOrder = null;

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
}
