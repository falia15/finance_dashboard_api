<?php

namespace App\Dto;

use App\Entity\IncomeType;
use App\Validator\IncomeType\CompleteIncomeTypeOrder;

/**
 * Payload of `PATCH /income_types/reorder`: every income type IRI, in the
 * new display order.
 */
final class IncomeTypeReorderInput
{
    /** @var list<IncomeType> */
    #[CompleteIncomeTypeOrder]
    public array $order = [];
}
