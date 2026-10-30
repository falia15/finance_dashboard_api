<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\IncomeTypeReorderInput;
use Doctrine\ORM\EntityManagerInterface;

/**
 * `PATCH /income_types/reorder`: `sortOrder` becomes each type's index in
 * the (already validated, complete) list.
 *
 * @implements ProcessorInterface<IncomeTypeReorderInput, null>
 */
final class IncomeTypeReorderProcessor implements ProcessorInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        foreach ($data->order as $index => $type) {
            $type->setSortOrder($index);
        }
        $this->entityManager->flush();

        return null;
    }
}
