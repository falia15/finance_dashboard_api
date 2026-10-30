<?php

namespace App\Tests\Api;

use App\Entity\IncomeType;

/**
 * Income type shared by the income rows of a test, for tests extending
 * ApiTestCase.
 */
trait IncomeFixtures
{
    private ?IncomeType $incomeType = null;

    private function incomeType(): IncomeType
    {
        return $this->incomeType ??= $this->persist((new IncomeType())->setName('Salary')->setSortOrder(1));
    }

    private function incomeTypeIri(): string
    {
        return '/api/income_types/'.$this->incomeType()->getId();
    }
}
