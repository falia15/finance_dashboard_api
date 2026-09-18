<?php

namespace App\Validator;

use App\Entity\HouseholdMember;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class UniqueActiveHouseholdOwnerValidator extends ConstraintValidator
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueActiveHouseholdOwner) {
            throw new UnexpectedTypeException($constraint, UniqueActiveHouseholdOwner::class);
        }

        if (!$value instanceof HouseholdMember) {
            throw new UnexpectedValueException($value, HouseholdMember::class);
        }

        if (!$value->isOwner() || null !== $value->getLeftAt()) {
            return;
        }

        $household = $value->getHousehold();
        if ($household === null) {
            return;
        }

        $qb = $this->entityManager->createQueryBuilder()
            ->select('COUNT(m.id)')
            ->from(HouseholdMember::class, 'm')
            ->andWhere('m.household = :household')
            ->andWhere('m.isOwner = true')
            ->andWhere('m.leftAt IS NULL')
            ->setParameter('household', $household);

        if (null !== $value->getId()) {
            $qb->andWhere('m.id != :id')->setParameter('id', $value->getId());
        }

        if ((int) $qb->getQuery()->getSingleScalarResult() > 0) {
            $this->context->buildViolation($constraint->message)
                ->atPath('isOwner')
                ->addViolation();
        }
    }
}
