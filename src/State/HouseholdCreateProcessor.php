<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Household;
use App\Entity\HouseholdMember;
use App\Http\ActiveProfileResolver;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * `POST /households`: the active profile becomes the household's first
 * member, and its owner.
 *
 * @implements ProcessorInterface<Household, Household>
 */
final class HouseholdCreateProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<Household, Household> $persistProcessor
     */
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persistProcessor,
        private readonly ActiveProfileResolver $activeProfileResolver,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Household
    {
        $data->addMember((new HouseholdMember())
            ->setProfile($this->activeProfileResolver->getActiveProfile())
            ->setIsOwner(true)
            ->setJoinedAt(new \DateTimeImmutable('today')));

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
