<?php

namespace App\Tests\Api;

use App\Entity\Household;
use App\Entity\HouseholdMember;
use App\Entity\Profile;

final class HouseholdMemberTest extends ApiTestCase
{
    use HouseholdFixtures;

    private Profile $alice;
    private Profile $bob;
    private Household $household;
    private HouseholdMember $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->alice = $this->persist((new Profile())->setName('Alice'));
        $this->bob = $this->persist((new Profile())->setName('Bob'));
        $this->household = $this->createHousehold($this->alice);
        $this->owner = $this->household->getMembers()->first();
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>|null
     */
    private function patchMember(HouseholdMember $member, array $payload, ?Profile $as = null): ?array
    {
        return $this->request('PATCH', '/api/household_members/'.$member->getId(), $payload, self::activeProfile($as ?? $this->alice));
    }

    private function reload(HouseholdMember $member): HouseholdMember
    {
        $this->entityManager()->clear();

        return $this->entityManager()->find(HouseholdMember::class, $member->getId());
    }

    public function testListsActiveAndPastMembers(): void
    {
        $this->addMember($this->household, externalLabel: 'Partner');
        $this->addMember($this->household, externalLabel: 'Former roommate', leftAt: new \DateTimeImmutable('2026-06-01'));

        $body = $this->request('GET', '/api/households/'.$this->household->getId().'/members', headers: self::activeProfile($this->alice));

        self::assertResponseIsSuccessful();
        self::assertSame(3, $body['totalItems']);
    }

    public function testMembersOfAnotherProfileHouseholdAreHidden(): void
    {
        $body = $this->request('GET', '/api/households/'.$this->household->getId().'/members', headers: self::activeProfile($this->bob));
        self::assertSame(0, $body['totalItems']);

        $this->request('GET', '/api/household_members/'.$this->owner->getId(), headers: self::activeProfile($this->bob));
        self::assertResponseStatusCodeSame(404);
    }

    public function testOwnerAddsAnExternalMember(): void
    {
        $body = $this->request('POST', '/api/households/'.$this->household->getId().'/members', ['externalLabel' => ' Partner '], self::activeProfile($this->alice));

        self::assertResponseStatusCodeSame(201);
        self::assertSame('/api/households/'.$this->household->getId(), $body['household']);
        self::assertSame('Partner', $body['externalLabel']);
        self::assertFalse($body['isOwner']);
        self::assertSame((new \DateTimeImmutable('today'))->format('Y-m-d'), substr($body['joinedAt'], 0, 10));
        self::assertNull($body['profile'] ?? null);
    }

    public function testAddingAMemberWithoutLabelIsRejected(): void
    {
        $body = $this->request('POST', '/api/households/'.$this->household->getId().'/members', ['externalLabel' => ''], self::activeProfile($this->alice));

        self::assertResponseStatusCodeSame(422);
        self::assertContains('externalLabel', self::violationPaths($body));
    }

    public function testOnlyTheOwnerAddsMembers(): void
    {
        $uri = '/api/households/'.$this->household->getId().'/members';

        $this->request('POST', $uri, ['externalLabel' => 'Partner'], self::activeProfile($this->bob));
        self::assertResponseStatusCodeSame(404);

        $this->addMember($this->household, profile: $this->bob);
        $this->request('POST', $uri, ['externalLabel' => 'Partner'], self::activeProfile($this->bob));
        self::assertResponseStatusCodeSame(403);
    }

    public function testDetachAMember(): void
    {
        $partner = $this->addMember($this->household, externalLabel: 'Partner');

        $body = $this->patchMember($partner, ['leftAt' => '2026-08-21']);

        self::assertResponseIsSuccessful();
        self::assertSame('2026-08-21', substr($body['leftAt'], 0, 10));
    }

    public function testDepartureIsFinal(): void
    {
        $partner = $this->addMember($this->household, externalLabel: 'Partner', leftAt: new \DateTimeImmutable('2026-06-01'));

        $body = $this->patchMember($partner, ['leftAt' => null]);
        self::assertResponseStatusCodeSame(422);
        self::assertContains('leftAt', self::violationPaths($body));

        $this->patchMember($partner, ['leftAt' => '2026-07-01']);
        self::assertResponseStatusCodeSame(422);
    }

    public function testCannotLeaveBeforeJoining(): void
    {
        $partner = $this->addMember($this->household, externalLabel: 'Partner');

        $body = $this->patchMember($partner, ['leftAt' => '2025-12-31']);

        self::assertResponseStatusCodeSame(422);
        self::assertContains('leftAt', self::violationPaths($body));
    }

    public function testOwnerCannotLeave(): void
    {
        $body = $this->patchMember($this->owner, ['leftAt' => '2026-08-21']);

        self::assertResponseStatusCodeSame(422);
        self::assertContains('leftAt', self::violationPaths($body));
    }

    public function testOwnershipCannotBeDropped(): void
    {
        $body = $this->patchMember($this->owner, ['isOwner' => false]);

        self::assertResponseStatusCodeSame(422);
        self::assertContains('isOwner', self::violationPaths($body));
    }

    public function testExternalMemberCannotBecomeOwner(): void
    {
        $partner = $this->addMember($this->household, externalLabel: 'Partner');

        $body = $this->patchMember($partner, ['isOwner' => true]);

        self::assertResponseStatusCodeSame(422);
        self::assertContains('isOwner', self::violationPaths($body));
        self::assertTrue($this->reload($this->owner)->isOwner());
    }

    public function testTransferOwnershipToARealMember(): void
    {
        $bobMember = $this->addMember($this->household, profile: $this->bob);

        $body = $this->patchMember($bobMember, ['isOwner' => true]);

        self::assertResponseIsSuccessful();
        self::assertTrue($body['isOwner']);
        self::assertFalse($this->reload($this->owner)->isOwner());
        self::assertTrue($this->reload($bobMember)->isOwner());
    }

    public function testOnlyTheOwnerManagesMembers(): void
    {
        $partner = $this->addMember($this->household, externalLabel: 'Partner');
        $this->addMember($this->household, profile: $this->bob);

        $this->patchMember($partner, ['leftAt' => '2026-08-21'], as: $this->bob);
        self::assertResponseStatusCodeSame(403);

        $this->request('DELETE', '/api/household_members/'.$partner->getId(), headers: self::activeProfile($this->bob));
        self::assertResponseStatusCodeSame(403);
    }

    public function testDeleteAMember(): void
    {
        $partner = $this->addMember($this->household, externalLabel: 'Partner');

        $this->request('DELETE', '/api/household_members/'.$partner->getId(), headers: self::activeProfile($this->alice));

        self::assertResponseStatusCodeSame(204);
        self::assertSame(1, $this->entityManager()->getRepository(HouseholdMember::class)->count());
    }

    public function testOwnerCannotBeDeleted(): void
    {
        $body = $this->request('DELETE', '/api/household_members/'.$this->owner->getId(), headers: self::activeProfile($this->alice));

        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('owner cannot be removed', $body['violations'][0]['message'] ?? '');
    }
}
