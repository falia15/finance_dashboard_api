<?php

namespace App\Repository;

use App\Entity\FixedExpense;
use App\Entity\Household;
use App\Entity\Profile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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

    /**
     * @return list<FixedExpense> the profile's individual lines shown for the year, by sort order
     */
    public function findIndividualShownIn(Profile $profile, int $year): array
    {
        return $this->shownIn($year)
            ->andWhere('f.profile = :profile')
            ->setParameter('profile', $profile)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param list<Household> $households
     *
     * @return list<FixedExpense> the households' lines shown for the year, by sort order
     */
    public function findForHouseholdsShownIn(array $households, int $year): array
    {
        if ([] === $households) {
            return [];
        }

        return $this->shownIn($year)
            ->andWhere('f.household IN (:households)')
            ->setParameter('households', $households)
            ->getQuery()
            ->getResult();
    }

    /**
     * Lines shown for the year: started at the latest that year, and not
     * deactivated before it.
     */
    private function shownIn(int $year): QueryBuilder
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.startYear <= :year')
            ->andWhere('f.endYear IS NULL OR f.endYear >= :year')
            ->setParameter('year', $year)
            ->orderBy('f.sortOrder')
            ->addOrderBy('f.id');
    }
}
