<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Entity\Trait\TimestampableTrait;
use App\Repository\ExpenseCategoryRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * `sortOrder` is client-writable for now — the doc's "auto-assigned at the
 * end" behavior needs a state processor, not implemented yet.
 */
#[ORM\Entity(repositoryClass: ExpenseCategoryRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(order: ['sortOrder' => 'ASC']),
        new Get(),
        new Post(),
        new Patch(),
        new Delete(),
    ],
)]
class ExpenseCategory
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $color = null;

    /** `order` avoided — reserved SQL keyword. */
    #[ORM\Column]
    private ?int $sortOrder = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $excludedFromTotals = false;

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

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;

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

    public function isExcludedFromTotals(): bool
    {
        return $this->excludedFromTotals;
    }

    public function setExcludedFromTotals(bool $excludedFromTotals): static
    {
        $this->excludedFromTotals = $excludedFromTotals;

        return $this;
    }
}
