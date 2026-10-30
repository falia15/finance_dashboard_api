<?php

namespace App\Tests\Api;

use App\Entity\Household;
use App\Entity\HouseholdMember;
use App\Entity\Profile;

/**
 * Builds households straight in the database (no API call), for tests
 * extending ApiTestCase.
 */
trait HouseholdFixtures
{
    private function createHousehold(Profile $owner, string $name = 'Home'): Household
    {
        $household = $this->persist((new Household())->setName($name));
        $this->addMember($household, profile: $owner, isOwner: true);

        return $household;
    }

    private function addMember(
        Household $household,
        ?Profile $profile = null,
        ?string $externalLabel = null,
        bool $isOwner = false,
        ?\DateTimeImmutable $leftAt = null,
    ): HouseholdMember {
        $member = (new HouseholdMember())
            ->setProfile($profile)
            ->setExternalLabel($externalLabel)
            ->setIsOwner($isOwner)
            ->setJoinedAt(new \DateTimeImmutable('2026-01-01'))
            ->setLeftAt($leftAt);
        $household->addMember($member);

        return $this->persist($member);
    }
}
