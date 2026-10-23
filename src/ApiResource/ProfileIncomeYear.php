<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use App\State\ProfileIncomeYearProvider;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * `GET /profiles/{profileId}/incomes?year=`: the profile's 12 months of a
 * year (always 12, empty ones included) — its individual incomes and its
 * share of the incomes of the households it is an active member of — with
 * the totals. Read model only, writes go through `/incomes`.
 */
#[ApiResource(operations: [
    new Get(
        uriTemplate: '/profiles/{profileId}/incomes',
        uriVariables: ['profileId'],
        provider: ProfileIncomeYearProvider::class,
        // Keeps `householdMember: null` (equal split) in householdShares
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
final class ProfileIncomeYear
{
    public function __construct(
        #[ApiProperty(identifier: true)]
        public readonly int $profileId,
        public readonly int $year,
        /** @var list<ProfileIncomeMonth> */
        #[ApiProperty(genId: false)]
        public readonly array $months,
        #[ApiProperty(genId: false)]
        public readonly ProfileIncomeTotals $totals,
    ) {
    }
}
