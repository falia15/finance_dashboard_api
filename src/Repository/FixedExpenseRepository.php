<?php

namespace App\Repository;

use App\Entity\FixedExpense;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FixedExpense>
 */
class FixedExpenseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FixedExpense::class);
    }

    /** Sort order placing a new line after the other lines of the same owner (profile or household). */
    public function nextSortOrder(FixedExpense $fixedExpense): int
    {
        $owner = null !== $fixedExpense->getProfile() ? 'profile' : 'household';

        $max = $this->createQueryBuilder('f')
            ->select('MAX(f.sortOrder)')
            ->andWhere(sprintf('f.%s = :owner', $owner))
            ->setParameter('owner', $fixedExpense->getProfile() ?? $fixedExpense->getHousehold())
            ->getQuery()
            ->getSingleScalarResult();

        return null === $max ? 0 : (int) $max + 1;
    }
}
