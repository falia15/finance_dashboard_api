<?php

namespace App\Entity;

/**
 * A row that can belong to a household (Expense, Income, FixedExpense), and
 * within it to one member — null member means split equally.
 */
interface HouseholdRowInterface
{
    public function getHousehold(): ?Household;

    public function getHouseholdMember(): ?HouseholdMember;
}
