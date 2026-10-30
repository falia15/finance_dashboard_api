<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use App\State\ProfileFixedExpenseYearProvider;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * `GET /profiles/{profileId}/fixed_expenses?year=`: the "Charges fixes" page
 * of a year — the profile's individual lines, one grid per household it is
 * an active member of (with its share of each month), and the totals.
 * Organised by line (lines × months grid). Read model only, writes go
 * through `/fixed_expenses` and `/fixed_expense_amounts`.
 */
#[ApiResource(operations: [
    new Get(
        uriTemplate: '/profiles/{profileId}/fixed_expenses',
        uriVariables: ['profileId'],
        provider: ProfileFixedExpenseYearProvider::class,
        // Keeps `householdMember: null` (equal split) and `endYear: null` (active line)
        normalizationContext: ['skip_null_values' => false],
        parameters: [
            'year' => new QueryParameter(
                schema: ['type' => 'integer'],
                description: 'Year to read, e.g. 2026',
                required: true,
                constraints: [new Assert\NotNull(), new Assert\Type('integer'), new Assert\Range(min: 2000, max: 2100)],
                castToNativeType: true,
            ),
        ],
    ),
])]
final class ProfileFixedExpenseYear
{
    public function __construct(
        #[ApiProperty(identifier: true)]
        public readonly int $profileId,
        public readonly int $year,
        /** @var list<ProfileFixedExpenseLine> */
        #[ApiProperty(genId: false)]
        public readonly array $lines,
        /** @var list<HouseholdFixedExpenseGrid> */
        #[ApiProperty(genId: false)]
        public readonly array $households,
        /** @var list<ProfileFixedExpenseMonth> always 12 */
        #[ApiProperty(genId: false)]
        public readonly array $months,
        #[ApiProperty(genId: false)]
        public readonly ProfileFixedExpenseTotals $totals,
    ) {
    }
}
