<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Expense;
use App\Entity\FixedExpense;
use App\Entity\FixedExpenseAmount;
use App\Entity\HouseholdMember;
use App\Entity\Income;
use App\Entity\Note;
use App\Http\ActiveProfileResolver;
use Doctrine\ORM\QueryBuilder;

/**
 * Restricts reads (collections and items, hence PATCH/DELETE too) to the
 * active profile (`X-Profile-Id` header): its own rows, plus the rows of
 * the households it is an active real member of.
 *
 * `Household` / `HouseholdMember` aren't scoped yet: the owner membership
 * isn't created on `POST /households` so far, a new household would be
 * invisible to its creator.
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

        // Alias of the entity carrying `profile` (+ `household`, except Note).
        $ownerAlias = match ($resourceClass) {
            Income::class, Expense::class, FixedExpense::class, Note::class => $rootAlias,
            FixedExpenseAmount::class => $queryNameGenerator->generateJoinAlias('fixedExpense'),
            default => null,
        };

        if (null === $ownerAlias) {
            return;
        }

        if (FixedExpenseAmount::class === $resourceClass) {
            $queryBuilder->innerJoin(sprintf('%s.fixedExpense', $rootAlias), $ownerAlias);
        }

        $profileParameter = $queryNameGenerator->generateParameterName('activeProfile');
        $queryBuilder->setParameter($profileParameter, $this->activeProfileResolver->getActiveProfile());

        $conditions = [sprintf('%s.profile = :%s', $ownerAlias, $profileParameter)];

        if (Note::class !== $resourceClass) {
            $memberAlias = $queryNameGenerator->generateJoinAlias('activeMember');
            $conditions[] = sprintf(
                'IDENTITY(%1$s.household) IN (SELECT IDENTITY(%2$s.household) FROM %3$s %2$s WHERE %2$s.profile = :%4$s AND %2$s.leftAt IS NULL)',
                $ownerAlias,
                $memberAlias,
                HouseholdMember::class,
                $profileParameter,
            );
        }

        $queryBuilder->andWhere($queryBuilder->expr()->orX(...$conditions));
    }
}
