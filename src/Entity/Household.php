<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Entity\Trait\TimestampableTrait;
use App\Repository\HouseholdRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HouseholdRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(operations: [
    new GetCollection(),
    new Get(),
    new Post(),
    new Patch(),
    new Delete(),
])]
class Household
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /** Embedded on read (not an IRI list) — a household's members are always shown with it, no separate call needed. */
    #[ORM\OneToMany(mappedBy: 'household', targetEntity: HouseholdMember::class)]
    #[ApiProperty(writable: false, readableLink: true)]
    private Collection $members;

    public function __construct()
    {
        $this->members = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, HouseholdMember>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }
}
