<?php

namespace App\Repository;

use App\Entity\Household;
use App\Entity\HouseholdMember;
use App\Entity\Profile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HouseholdMember>
 */
class HouseholdMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HouseholdMember::class);
    }

    /**
     * The profile's current (not left) membership of the household, if any.
     */
    public function findActiveMembership(Household $household, Profile $profile): ?HouseholdMember
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.household = :household')
            ->andWhere('m.profile = :profile')
            ->andWhere('m.leftAt IS NULL')
            ->setParameter('household', $household)
            ->setParameter('profile', $profile)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Active members (real or external), i.e. the household's current split divisor.
     */
    public function countActiveMembers(Household $household): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.household = :household')
            ->andWhere('m.leftAt IS NULL')
            ->setParameter('household', $household)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<HouseholdMember> active owners other than the given member
     */
    public function findOtherActiveOwners(HouseholdMember $member): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.household = :household')
            ->andWhere('m.isOwner = true')
            ->andWhere('m.leftAt IS NULL')
            ->andWhere('m != :member')
            ->setParameter('household', $member->getHousehold())
            ->setParameter('member', $member)
            ->getQuery()
            ->getResult();
    }
}
