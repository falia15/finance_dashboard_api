<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\FixedExpense;
use App\Entity\FixedExpenseAmount;
use App\Repository\FixedExpenseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * `POST /fixed_expenses`: the new line goes at the end of its owner's
 * list, and its `initialAmount` (if any) becomes one FixedExpenseAmount
 * per month, written in the same flush as the line.
 *
 * @implements ProcessorInterface<FixedExpense, FixedExpense>
 */
final class FixedExpenseCreateProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<FixedExpense, FixedExpense> $persistProcessor
     */
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persistProcessor,
        private readonly FixedExpenseRepository $fixedExpenseRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): FixedExpense
    {
        $data->setSortOrder($this->fixedExpenseRepository->nextSortOrder($data));

        $initialAmount = $data->getInitialAmount();
        foreach ($initialAmount?->months() ?? [] as $month) {
            $this->entityManager->persist((new FixedExpenseAmount())
                ->setFixedExpense($data)
                ->setYear($initialAmount->year)
                ->setMonth($month)
                ->setAmount($initialAmount->amount));
        }

        // Persists the line and flushes everything at once
        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
