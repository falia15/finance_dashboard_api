<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\FixedExpense;
use App\Repository\FixedExpenseAmountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * `PATCH /fixed_expenses/{id}`: deactivating the line (setting
 * `endYear`/`endMonth`) deletes its amounts after the end, in the same
 * transaction as the update. Reactivating it (clearing the end) doesn't
 * restore them.
 *
 * @implements ProcessorInterface<FixedExpense, FixedExpense>
 */
final class FixedExpenseUpdateProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<FixedExpense, FixedExpense> $persistProcessor
     */
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persistProcessor,
        private readonly FixedExpenseAmountRepository $fixedExpenseAmountRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): FixedExpense
    {
        return $this->entityManager->wrapInTransaction(function () use ($data, $operation, $uriVariables, $context): FixedExpense {
            if ($data->isDeactivated()) {
                $this->fixedExpenseAmountRepository->deleteAfter($data, $data->getEndYear(), $data->getEndMonth());
            }

            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        });
    }
}
