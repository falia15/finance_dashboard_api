<?php

namespace App\ApiResource;

/** An individual income row of the profile, in ProfileIncomeMonth::$incomes. */
final class ProfileIncomeLine
{
    public function __construct(
        public readonly int $id,
        /** IncomeType IRI */
        public readonly string $type,
        public readonly string $amount,
    ) {
    }
}
