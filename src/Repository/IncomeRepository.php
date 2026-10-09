<?php

namespace App\Repository;

use App\Entity\Household;
use App\Entity\Income;
use App\Entity\Profile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Income>
 */
class IncomeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Income::class);
    }

    /**
     * @return list<Income> the profile's individual rows of the year, by month then type order
     */
    public function findIndividualForYear(Profile $profile, int $year): array
    {
        return $this->createQueryBuilder('i')
            ->innerJoin('i.type', 't')
            ->andWhere('i.profile = :profile')
            ->andWhere('i.year = :year')
            ->setParameter('profile', $profile)
            ->setParameter('year', $year)
            ->orderBy('i.month')
            ->addOrderBy('t.sortOrder')
            ->addOrderBy('i.id')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param list<Household> $households
     *
     * @return list<Income> the households' rows of the year, by month then type order
     */
    public function findForHouseholdsAndYear(array $households, int $year): array
    {
        if ([] === $households) {
            return [];
        }

        return $this->createQueryBuilder('i')
            ->innerJoin('i.type', 't')
            ->andWhere('i.household IN (:households)')
            ->andWhere('i.year = :year')
            ->setParameter('households', $households)
            ->setParameter('year', $year)
            ->orderBy('i.month')
            ->addOrderBy('t.sortOrder')
            ->addOrderBy('i.id')
            ->getQuery()
            ->getResult();
    }
}
