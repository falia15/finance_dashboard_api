<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Expense;
use App\Entity\FixedExpense;
use App\Entity\FixedExpenseAmount;
use App\Entity\Household;
use App\Entity\HouseholdMember;
use App\Entity\Income;
use App\Entity\Note;
use App\Http\ActiveProfileResolver;
use Doctrine\ORM\QueryBuilder;

/**
 * Restricts reads (collections and items, hence PATCH/DELETE too) to the
 * active profile (`X-Profile-Id` header): its own rows, plus the rows of
 * the households it is an active real member of — and those households
 * and their members themselves.
 *
 * Writes are checked by App\Validator\InActiveProfileScope.
 */
final class ActiveProfileExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(private readonly ActiveProfileResolver $activeProfileResolver)
    {
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $this->scopeToActiveProfile($queryBuilder, $queryNameGenerator, $resourceClass);
    }

    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, ?Operation $operation = null, array $context = []): void
    {
        $this->scopeToActiveProfile($queryBuilder, $queryNameGenerator, $resourceClass);
    }

    private function scopeToActiveProfile(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass): void
    {
        $rootAlias = $queryBuilder->getRootAliases()[0];

        // Expression holding the household id to check the membership of,
        // and alias of the entity carrying `profile` when there is one.
        [$householdExpression, $ownerAlias] = match ($resourceClass) {
            Household::class => [sprintf('%s.id', $rootAlias), null],
            HouseholdMember::class => [sprintf('IDENTITY(%s.household)', $rootAlias), null],
            Income::class, Expense::class, FixedExpense::class => [sprintf('IDENTITY(%s.household)', $rootAlias), $rootAlias],
            Note::class => [null, $rootAlias],
            FixedExpenseAmount::class => [null, $queryNameGenerator->generateJoinAlias('fixedExpense')],
            default => [null, null],
        };

        if (null === $householdExpression && null === $ownerAlias) {
            return;
        }

        if (FixedExpenseAmount::class === $resourceClass) {
            $queryBuilder->innerJoin(sprintf('%s.fixedExpense', $rootAlias), $ownerAlias);
            $householdExpression = sprintf('IDENTITY(%s.household)', $ownerAlias);
        }

        $profileParameter = $queryNameGenerator->generateParameterName('activeProfile');
        $queryBuilder->setParameter($profileParameter, $this->activeProfileResolver->getActiveProfile());

        $conditions = [];

        if (null !== $ownerAlias) {
            $conditions[] = sprintf('%s.profile = :%s', $ownerAlias, $profileParameter);
        }

        if (null !== $householdExpression) {
            $memberAlias = $queryNameGenerator->generateJoinAlias('activeMember');
            $conditions[] = sprintf(
                '%1$s IN (SELECT IDENTITY(%2$s.household) FROM %3$s %2$s WHERE %2$s.profile = :%4$s AND %2$s.leftAt IS NULL)',
                $householdExpression,
                $memberAlias,
                HouseholdMember::class,
                $profileParameter,
            );
        }

        $queryBuilder->andWhere($queryBuilder->expr()->orX(...$conditions));
    }
}
