<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\HouseholdMember;
use App\Repository\HouseholdMemberRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * `PATCH /household_members/{id}`: promoting a member to owner
 * (`isOwner: true`) transfers the ownership — the previous owner is
 * demoted in the same flush. The rules themselves are checked by
 * ValidHouseholdMembership.
 *
 * @implements ProcessorInterface<HouseholdMember, HouseholdMember>
 */
final class HouseholdMemberUpdateProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<HouseholdMember, HouseholdMember> $persistProcessor
     */
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persistProcessor,
        private readonly HouseholdMemberRepository $householdMemberRepository,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): HouseholdMember
    {
        $previous = $context['previous_data'] ?? null;

        if ($data->isOwner() && $previous instanceof HouseholdMember && !$previous->isOwner()) {
            foreach ($this->householdMemberRepository->findOtherActiveOwners($data) as $formerOwner) {
                $formerOwner->setIsOwner(false);
            }
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
