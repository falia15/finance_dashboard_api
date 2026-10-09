<?php

namespace App\Repository;

use App\Entity\IncomeType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<IncomeType>
 */
class IncomeTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IncomeType::class);
    }

    /** Sort order placing a new type after the existing ones. */
    public function nextSortOrder(): int
    {
        $max = $this->createQueryBuilder('t')
            ->select('MAX(t.sortOrder)')
            ->getQuery()
            ->getSingleScalarResult();

        return null === $max ? 0 : (int) $max + 1;
    }
}
