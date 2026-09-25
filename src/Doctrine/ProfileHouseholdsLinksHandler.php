<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\HouseholdMember;
use Doctrine\ORM\QueryBuilder;

/**
 * `GET /profiles/{profileId}/households`: households the profile is an
 * active real member of. ActiveProfileExtension still applies on top, so
 * another profile's households only show when shared with the active one.
 */
final class ProfileHouseholdsLinksHandler
{
    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public static function handleLinks(QueryBuilder $queryBuilder, array $uriVariables, QueryNameGeneratorInterface $queryNameGenerator, array $context): void
    {
        $rootAlias = $queryBuilder->getRootAliases()[0];
        $memberAlias = $queryNameGenerator->generateJoinAlias('profileMember');
        $profileParameter = $queryNameGenerator->generateParameterName('profileId');

        $queryBuilder
            ->andWhere(sprintf(
                '%1$s.id IN (SELECT IDENTITY(%2$s.household) FROM %3$s %2$s WHERE IDENTITY(%2$s.profile) = :%4$s AND %2$s.leftAt IS NULL)',
                $rootAlias,
                $memberAlias,
                HouseholdMember::class,
                $profileParameter,
            ))
            ->setParameter($profileParameter, $uriVariables['profileId']);
    }
}
