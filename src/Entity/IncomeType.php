<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Dto\IncomeTypeReorderInput;
use App\Entity\Trait\TimestampableTrait;
use App\Repository\IncomeTypeRepository;
use App\State\IncomeTypeCreateProcessor;
use App\State\IncomeTypeReorderProcessor;
use App\Validator\IncomeType\IncomeTypeNotInUse;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Kind of income (salary, rental income, family allowance...), shared by
 * every profile, managed like ExpenseCategory so incomes can be filtered by
 * type.
 *
 * `sortOrder` isn't writable: a new type goes at the end, and the list is
 * reordered as a whole through `PATCH /income_types/reorder`. A type used by
 * an income can't be deleted.
 */
#[ORM\Entity(repositoryClass: IncomeTypeRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[IncomeTypeNotInUse(groups: ['delete'])]
#[ApiResource(
    operations: [
        new GetCollection(order: ['sortOrder' => 'ASC', 'id' => 'ASC']),
        new Get(),
        new Post(processor: IncomeTypeCreateProcessor::class),
        // Before `/income_types/{id}` so that "reorder" isn't read as an id
        new Patch(
            uriTemplate: '/income_types/reorder',
            status: 204,
            input: IncomeTypeReorderInput::class,
            output: false,
            read: false,
            processor: IncomeTypeReorderProcessor::class,
        ),
        new Patch(),
        new Delete(validate: true, validationContext: ['groups' => ['delete']]),
    ],
)]
class IncomeType
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

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $color = null;

    /** `order` avoided — reserved SQL keyword. */
    #[ORM\Column]
    #[ApiProperty(writable: false)]
    private ?int $sortOrder = null;

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
}
