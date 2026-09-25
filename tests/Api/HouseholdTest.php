<?php

namespace App\Tests\Api;

use App\Entity\Household;
use App\Entity\HouseholdMember;
use App\Entity\Income;
use App\Entity\Profile;

final class HouseholdTest extends ApiTestCase
{
    use HouseholdFixtures;

    private Profile $alice;
    private Profile $bob;

    protected function setUp(): void
    {
        parent::setUp();

        $this->alice = $this->persist((new Profile())->setName('Alice'));
        $this->bob = $this->persist((new Profile())->setName('Bob'));
    }

    public function testCreatorBecomesTheOwner(): void
    {
        $body = $this->request('POST', '/api/households', ['name' => 'Couple'], self::activeProfile($this->alice));

        self::assertResponseStatusCodeSame(201);
        self::assertSame('Couple', $body['name']);
        self::assertCount(1, $body['members']);
        self::assertSame('/api/profiles/'.$this->alice->getId(), $body['members'][0]['profile']);
        self::assertTrue($body['members'][0]['isOwner']);
        self::assertSame((new \DateTimeImmutable('today'))->format('Y-m-d'), substr($body['members'][0]['joinedAt'], 0, 10));
        self::assertNull($body['members'][0]['leftAt'] ?? null);
    }

    public function testCreateWithoutActiveProfileIsRejected(): void
    {
        $this->request('POST', '/api/households', ['name' => 'Couple']);

        self::assertResponseStatusCodeSame(400);
        self::assertSame(0, $this->entityManager()->getRepository(Household::class)->count());
    }

    public function testCreateWithBlankNameIsRejected(): void
    {
        $body = $this->request('POST', '/api/households', ['name' => '  '], self::activeProfile($this->alice));

        self::assertResponseStatusCodeSame(422);
        self::assertContains('name', self::violationPaths($body));
    }

    public function testCollectionOnlyContainsHouseholdsOfTheActiveProfile(): void
    {
        $this->createHousehold($this->alice, 'Alice home');
        $this->createHousehold($this->bob, 'Bob home');
        $shared = $this->createHousehold($this->bob, 'Shared');
        $this->addMember($shared, profile: $this->alice);
        $left = $this->createHousehold($this->bob, 'Left');
        $this->addMember($left, profile: $this->alice, leftAt: new \DateTimeImmutable('2026-06-01'));

        $body = $this->request('GET', '/api/households', headers: self::activeProfile($this->alice));

        self::assertResponseIsSuccessful();
        $names = array_column($body['member'], 'name');
        sort($names);
        self::assertSame(['Alice home', 'Shared'], $names);
    }

    public function testProfileHouseholdsRoute(): void
    {
        $this->createHousehold($this->alice, 'Alice home');
        $this->createHousehold($this->bob, 'Bob home');

        $body = $this->request('GET', '/api/profiles/'.$this->alice->getId().'/households', headers: self::activeProfile($this->alice));

        self::assertResponseIsSuccessful();
        self::assertSame(['Alice home'], array_column($body['member'], 'name'));
    }

    public function testHouseholdOfAnotherProfileIsNotFound(): void
    {
        $household = $this->createHousehold($this->bob);
        $headers = self::activeProfile($this->alice);

        $this->request('GET', '/api/households/'.$household->getId(), headers: $headers);
        self::assertResponseStatusCodeSame(404);

        $this->request('PATCH', '/api/households/'.$household->getId(), ['name' => 'Hijacked'], $headers);
        self::assertResponseStatusCodeSame(404);

        $this->request('DELETE', '/api/households/'.$household->getId(), headers: $headers);
        self::assertResponseStatusCodeSame(404);
    }

    public function testOwnerCanRename(): void
    {
        $household = $this->createHousehold($this->alice);

        $body = $this->request('PATCH', '/api/households/'.$household->getId(), ['name' => 'Renamed'], self::activeProfile($this->alice));

        self::assertResponseIsSuccessful();
        self::assertSame('Renamed', $body['name']);
    }

    public function testNonOwnerMemberCanReadButNotManage(): void
    {
        $household = $this->createHousehold($this->alice);
        $this->addMember($household, profile: $this->bob);
        $headers = self::activeProfile($this->bob);

        $this->request('GET', '/api/households/'.$household->getId(), headers: $headers);
        self::assertResponseIsSuccessful();

        $this->request('PATCH', '/api/households/'.$household->getId(), ['name' => 'Renamed'], $headers);
        self::assertResponseStatusCodeSame(403);

        $this->request('DELETE', '/api/households/'.$household->getId(), headers: $headers);
        self::assertResponseStatusCodeSame(403);
    }

    public function testDeleteRemovesTheMembers(): void
    {
        $household = $this->createHousehold($this->alice);
        $this->addMember($household, externalLabel: 'Partner');

        $this->request('DELETE', '/api/households/'.$household->getId(), headers: self::activeProfile($this->alice));

        self::assertResponseStatusCodeSame(204);
        self::assertSame(0, $this->entityManager()->getRepository(Household::class)->count());
        self::assertSame(0, $this->entityManager()->getRepository(HouseholdMember::class)->count());
    }

    public function testDeleteWithAttachedRowsIsRejected(): void
    {
        $household = $this->createHousehold($this->alice);
        $this->persist((new Income())->setHousehold($household)->setYear(2026)->setMonth(9)->setNetPay('1000.00'));

        $body = $this->request('DELETE', '/api/households/'.$household->getId(), headers: self::activeProfile($this->alice));

        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('cannot be deleted', $body['violations'][0]['message'] ?? '');
        self::assertSame(1, $this->entityManager()->getRepository(Household::class)->count());
    }
}
