<?php

namespace App\EventListener;

use App\Entity\Expense;
use App\Entity\FixedExpenseAmount;
use App\Entity\Household;
use App\Entity\Income;
use App\Repository\HouseholdMemberRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

/**
 * Freezes `appliedDivisor` on a row: the household's active member count
 * (1 for an individual row) at the time the row is entered. Later member
 * changes never touch it — only moving an income/expense to another
 * household (or to/from individual) recomputes it.
 *
 * A `FixedExpenseAmount` takes the divisor of its fixed expense's
 * household when the month is created, and keeps it afterwards.
 */
#[AsDoctrineListener(event: Events::onFlush)]
final class AppliedDivisorListener
{
    public function __construct(private readonly HouseholdMemberRepository $householdMemberRepository)
    {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $entityManager = $args->getObjectManager();
        $unitOfWork = $entityManager->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            $household = match (true) {
                $entity instanceof Income, $entity instanceof Expense => $entity->getHousehold(),
                $entity instanceof FixedExpenseAmount => $entity->getFixedExpense()?->getHousehold(),
                default => false,
            };

            if (false !== $household) {
                $entity->setAppliedDivisor($this->divisorFor($household));
                $unitOfWork->recomputeSingleEntityChangeSet($entityManager->getClassMetadata($entity::class), $entity);
            }
        }

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if (($entity instanceof Income || $entity instanceof Expense)
                && \array_key_exists('household', $unitOfWork->getEntityChangeSet($entity))) {
                $entity->setAppliedDivisor($this->divisorFor($entity->getHousehold()));
                $unitOfWork->recomputeSingleEntityChangeSet($entityManager->getClassMetadata($entity::class), $entity);
            }
        }
    }

    private function divisorFor(?Household $household): int
    {
        // A household always has its owner as active member, max() only guards corrupted data
        return null === $household ? 1 : max(1, $this->householdMemberRepository->countActiveMembers($household));
    }
}
