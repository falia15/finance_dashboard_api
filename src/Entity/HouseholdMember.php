<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use App\Entity\Trait\TimestampableTrait;
use App\Repository\HouseholdMemberRepository;
use App\Validator\ExactlyOneOf;
use App\Validator\UniqueActiveHouseholdOwner;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * No GetCollection/Post here — listing and adding a member go through the
 * `/households/{id}/members` custom sub-resource (not implemented yet).
 * Only what's already scoped as standard here: read one, detach
 * (`leftAt`), transfer ownership (`isOwner`), or delete a mistaken entry.
 */
#[ORM\Entity(repositoryClass: HouseholdMemberRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ExactlyOneOf(first: 'profile', second: 'externalLabel')]
#[UniqueActiveHouseholdOwner]
#[ApiResource(operations: [
    new Get(),
    new Patch(),
    new Delete(),
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

    public function isOwner(): bool
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
