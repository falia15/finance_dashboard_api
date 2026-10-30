<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Dto\HouseholdMemberInput;
use App\Entity\Trait\TimestampableTrait;
use App\Repository\HouseholdMemberRepository;
use App\Security\HouseholdVoter;
use App\State\HouseholdMemberAddProcessor;
use App\State\HouseholdMemberUpdateProcessor;
use App\Validator\Common\ExactlyOneOf;
use App\Validator\Household\HasNoEntries;
use App\Validator\Household\NotHouseholdOwner;
use App\Validator\Household\ValidHouseholdMembership;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Listed and added through the `/households/{householdId}/members`
 * sub-resource. Standard item routes: read one, detach (`leftAt`),
 * transfer ownership (`isOwner`), or delete a mistaken entry (not once
 * rows are assigned to it). Only the household's active owner can change
 * its members.
 */
#[ORM\Entity(repositoryClass: HouseholdMemberRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ExactlyOneOf(first: 'profile', second: 'externalLabel')]
#[ValidHouseholdMembership]
#[NotHouseholdOwner(groups: ['delete'])]
#[HasNoEntries(field: 'householdMember', groups: ['delete'])]
#[ApiResource(operations: [
    new GetCollection(
        uriTemplate: '/households/{householdId}/members',
        uriVariables: ['householdId' => new Link(fromClass: Household::class, toProperty: 'household')],
    ),
    new Post(
        uriTemplate: '/households/{householdId}/members',
        uriVariables: ['householdId' => new Link(fromClass: Household::class, toProperty: 'household')],
        input: HouseholdMemberInput::class,
        read: false,
        processor: HouseholdMemberAddProcessor::class,
    ),
    new Get(),
    new Patch(
        security: "is_granted('".HouseholdVoter::MANAGE."', object)",
        processor: HouseholdMemberUpdateProcessor::class,
    ),
    new Delete(
        security: "is_granted('".HouseholdVoter::MANAGE."', object)",
        validate: true,
        validationContext: ['groups' => ['delete']],
    ),
])]
class HouseholdMember
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Household::class, inversedBy: 'members')]
    #[ORM\JoinColumn(nullable: false)]
    #[ApiProperty(writable: false)]
    private ?Household $household = null;

    #[ORM\ManyToOne(targetEntity: Profile::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[ApiProperty(writable: false)]
    private ?Profile $profile = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[ApiProperty(writable: false)]
    private ?string $externalLabel = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $isOwner = false;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[ApiProperty(writable: false)]
    private ?\DateTimeImmutable $joinedAt = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $leftAt = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getProfile(): ?Profile
    {
        return $this->profile;
    }

    public function setProfile(?Profile $profile): static
    {
        $this->profile = $profile;

        return $this;
    }

    public function getExternalLabel(): ?string
    {
        return $this->externalLabel;
    }

    public function setExternalLabel(?string $externalLabel): static
    {
        $this->externalLabel = $externalLabel;

        return $this;
    }

    /** Hidden from the API: exposed as `owner`, the field is `isOwner` (see getIsOwner()). */
    #[ApiProperty(readable: false, writable: false)]
    public function isOwner(): bool
    {
        return $this->isOwner;
    }

    public function getIsOwner(): bool
    {
        return $this->isOwner;
    }

    public function setIsOwner(bool $isOwner): static
    {
        $this->isOwner = $isOwner;

        return $this;
    }

    public function getJoinedAt(): ?\DateTimeImmutable
    {
        return $this->joinedAt;
    }

    public function setJoinedAt(\DateTimeImmutable $joinedAt): static
    {
        $this->joinedAt = $joinedAt;

        return $this;
    }

    public function getLeftAt(): ?\DateTimeImmutable
    {
        return $this->leftAt;
    }

    public function setLeftAt(?\DateTimeImmutable $leftAt): static
    {
        $this->leftAt = $leftAt;

        return $this;
    }
}
