<?php

namespace App\State;

use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use App\ApiResource\FixedExpenseMonthAmount;
use App\Dto\FixedExpenseApplyToYearInput;
use App\Dto\FixedExpenseApplyToYearOutput;
use App\Entity\FixedExpense;
use App\Entity\FixedExpenseAmount;
use App\Repository\FixedExpenseAmountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * `POST /fixed_expenses/{id}/apply_to_year`: copies the amount of one month
 * to every month of the year the line covers — empty months are created,
 * filled ones overwritten (keeping their frozen `appliedDivisor`). One
 * flush, so all months are written or none.
 *
 * The line itself was read by API Platform (`read_data`), scoped to the
 * active profile: a foreign line is a 404 before reaching here.
 *
 * @implements ProcessorInterface<FixedExpenseApplyToYearInput, FixedExpenseApplyToYearOutput>
 */
final class FixedExpenseApplyToYearProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly FixedExpenseAmountRepository $fixedExpenseAmountRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly IriConverterInterface $iriConverter,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): FixedExpenseApplyToYearOutput
    {
        /** @var FixedExpense $fixedExpense */
        $fixedExpense = $context['read_data'];
        $amounts = $this->fixedExpenseAmountRepository->findForLinesAndYear([$fixedExpense], $data->year);
        $amountsByMonth = array_combine(array_map(static fn (FixedExpenseAmount $amount) => $amount->getMonth(), $amounts), $amounts);

        // Nothing to copy from an empty month — also covers a year outside the active period
        $source = $amountsByMonth[$data->month] ?? null;
        if (null === $source) {
            throw $this->emptySourceMonth($data);
        }

        for ($month = 1; $month <= 12; ++$month) {
            if (!$fixedExpense->covers($data->year, $month)) {
                continue;
            }

            $amountsByMonth[$month] ??= $this->newAmount($fixedExpense, $data->year, $month);
            $amountsByMonth[$month]->setAmount($source->getAmount());
        }

        $this->entityManager->flush();
        ksort($amountsByMonth);

        return new FixedExpenseApplyToYearOutput(
            $this->iriConverter->getIriFromResource($fixedExpense),
            $data->year,
            array_values(array_map(
                static fn (FixedExpenseAmount $amount) => new FixedExpenseMonthAmount($amount->getId(), $amount->getMonth(), $amount->getAmount()),
                $amountsByMonth,
            )),
        );
    }

    private function newAmount(FixedExpense $fixedExpense, int $year, int $month): FixedExpenseAmount
    {
        $amount = (new FixedExpenseAmount())
            ->setFixedExpense($fixedExpense)
            ->setYear($year)
            ->setMonth($month);
        $this->entityManager->persist($amount);

        return $amount;
    }

    /**
     * A 422 on `month`, in the same format as the constraint violations.
     */
    private function emptySourceMonth(FixedExpenseApplyToYearInput $input): ValidationException
    {
        $message = 'This month has no amount to apply to the year.';

        return new ValidationException(new ConstraintViolationList([
            new ConstraintViolation($message, $message, [], $input, 'month', $input->month),
        ]));
    }
}
