<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Doctrine\ProfileHouseholdsLinksHandler;
use App\Entity\Trait\TimestampableTrait;
use App\Repository\HouseholdRepository;
use App\Security\HouseholdVoter;
use App\State\HouseholdCreateProcessor;
use App\Validator\HouseholdHasNoEntries;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Only visible to its active real members (see ActiveProfileExtension),
 * only managed (rename, delete) by its active owner.
 */
#[ORM\Entity(repositoryClass: HouseholdRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[HouseholdHasNoEntries(groups: ['delete'])]
#[ApiResource(operations: [
    new GetCollection(),
    new GetCollection(
        uriTemplate: '/profiles/{profileId}/households',
        uriVariables: ['profileId' => new Link(fromClass: Profile::class)],
        stateOptions: new Options(handleLinks: [ProfileHouseholdsLinksHandler::class, 'handleLinks']),
    ),
    new Get(),
    new Post(processor: HouseholdCreateProcessor::class),
    new Patch(security: "is_granted('".HouseholdVoter::MANAGE."', object)"),
    new Delete(
        security: "is_granted('".HouseholdVoter::MANAGE."', object)",
        validate: true,
        validationContext: ['groups' => ['delete']],
    ),
])]
class Household
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(normalizer: 'trim')]
    #[Assert\Length(max: 255)]
    private ?string $name = null;

    /** Embedded on read (not an IRI list) — a household's members are always shown with it, no separate call needed. */
    #[ORM\OneToMany(mappedBy: 'household', targetEntity: HouseholdMember::class, cascade: ['persist', 'remove'])]
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

    public function addMember(HouseholdMember $member): static
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
            $member->setHousehold($this);
        }

        return $this;
    }
}
