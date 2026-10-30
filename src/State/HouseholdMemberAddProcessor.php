<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\HouseholdMemberInput;
use App\Entity\HouseholdMember;
use App\Repository\HouseholdRepository;
use App\Security\HouseholdVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * `POST /households/{householdId}/members`: adds an external member
 * (joined today, never owner). 404 if the active profile isn't a member
 * of the household, 403 if it isn't its owner.
 *
 * @implements ProcessorInterface<HouseholdMemberInput, HouseholdMember>
 */
final class HouseholdMemberAddProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly HouseholdRepository $householdRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): HouseholdMember
    {
        $household = $this->householdRepository->find($uriVariables['householdId']);

        if (null === $household || !$this->security->isGranted(HouseholdVoter::VIEW, $household)) {
            throw new NotFoundHttpException('Household not found.');
        }

        if (!$this->security->isGranted(HouseholdVoter::MANAGE, $household)) {
            throw new AccessDeniedHttpException('Only the household owner can add members.');
        }

        $member = (new HouseholdMember())
            ->setExternalLabel(trim($data->externalLabel))
            ->setJoinedAt(new \DateTimeImmutable('today'));
        $household->addMember($member);

        $this->entityManager->persist($member);
        $this->entityManager->flush();

        return $member;
    }
}
