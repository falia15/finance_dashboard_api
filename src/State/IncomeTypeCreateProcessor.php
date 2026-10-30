<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\IncomeType;
use App\Repository\IncomeTypeRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * `POST /income_types`: the new type goes at the end of the list.
 *
 * @implements ProcessorInterface<IncomeType, IncomeType>
 */
final class IncomeTypeCreateProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<IncomeType, IncomeType> $persistProcessor
     */
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persistProcessor,
        private readonly IncomeTypeRepository $incomeTypeRepository,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): IncomeType
    {
        $data->setSortOrder($this->incomeTypeRepository->nextSortOrder());

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
