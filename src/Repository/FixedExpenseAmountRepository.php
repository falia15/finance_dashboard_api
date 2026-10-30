<?php

namespace App\Repository;

use App\Entity\FixedExpense;
use App\Entity\FixedExpenseAmount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FixedExpenseAmount>
 */
class FixedExpenseAmountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FixedExpenseAmount::class);
    }

    /**
     * @param list<FixedExpense> $fixedExpenses
     *
     * @return list<FixedExpenseAmount> the lines' amounts of the year, by month
     */
    public function findForLinesAndYear(array $fixedExpenses, int $year): array
    {
        if ([] === $fixedExpenses) {
            return [];
        }

        return $this->createQueryBuilder('a')
            ->andWhere('a.fixedExpense IN (:fixedExpenses)')
            ->andWhere('a.year = :year')
            ->setParameter('fixedExpenses', $fixedExpenses)
            ->setParameter('year', $year)
            ->orderBy('a.month')
            ->getQuery()
            ->getResult();
    }

    /**
     * Deletes the line's amounts after the given month (later months of
     * that year and every later year), in a single DQL query.
     */
    public function deleteAfter(FixedExpense $fixedExpense, int $year, int $month): void
    {
        $this->createQueryBuilder('a')
            ->delete()
            ->andWhere('a.fixedExpense = :fixedExpense')
            ->andWhere('a.year > :year OR (a.year = :year AND a.month > :month)')
            ->setParameter('fixedExpense', $fixedExpense)
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->getQuery()
            ->execute();
    }
}
