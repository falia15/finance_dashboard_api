<?php

namespace App\Tests\Api;

use App\Entity\Household;
use App\Entity\Profile;

/**
 * Covers App\Validator\InActiveProfileScope through `Income` and `Note`,
 * the other scoped resources share the same constraint.
 */
final class ActiveProfileWriteScopeTest extends ApiTestCase
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

    /**
     * @param array<string, mixed> $owner e.g. ['profile' => '/api/profiles/1']
     *
     * @return array<string, mixed>|null
     */
    private function postIncome(array $owner, Profile $as): ?array
    {
        return $this->request('POST', '/api/incomes', $owner + ['year' => 2026, 'month' => 9, 'netPay' => '1000.00'], self::activeProfile($as));
    }

    private static function iri(Profile|Household $entity): string
    {
        return ($entity instanceof Profile ? '/api/profiles/' : '/api/households/').$entity->getId();
    }

    public function testRowOfTheActiveProfileIsAccepted(): void
    {
        $this->postIncome(['profile' => self::iri($this->alice)], as: $this->alice);

        self::assertResponseStatusCodeSame(201);
    }

    public function testRowOfAnotherProfileIsRejected(): void
    {
        $body = $this->postIncome(['profile' => self::iri($this->bob)], as: $this->alice);

        self::assertResponseStatusCodeSame(422);
        self::assertContains('profile', self::violationPaths($body));
    }

    public function testRowOfAMemberHouseholdIsAccepted(): void
    {
        $household = $this->createHousehold($this->alice);

        $this->postIncome(['household' => self::iri($household)], as: $this->alice);

        self::assertResponseStatusCodeSame(201);
    }

    public function testRowOfAForeignHouseholdIsRejected(): void
    {
        $household = $this->createHousehold($this->bob);

        $this->postIncome(['household' => self::iri($household)], as: $this->alice);

        // The household IRI doesn't even resolve: households are scoped too
        self::assertResponseStatusCodeSame(400);
    }

    public function testRowOfAHouseholdTheProfileLeftIsRejected(): void
    {
        $household = $this->createHousehold($this->bob);
        $this->addMember($household, profile: $this->alice, leftAt: new \DateTimeImmutable('2026-06-01'));

        $this->postIncome(['household' => self::iri($household)], as: $this->alice);

        self::assertResponseStatusCodeSame(400);
    }

    public function testMovingARowToAnotherProfileIsRejected(): void
    {
        $body = $this->postIncome(['profile' => self::iri($this->alice)], as: $this->alice);

        $body = $this->request('PATCH', $body['@id'], ['profile' => self::iri($this->bob)], self::activeProfile($this->alice));

        self::assertResponseStatusCodeSame(422);
        self::assertContains('profile', self::violationPaths($body));
    }

    public function testNoteOfAnotherProfileIsRejected(): void
    {
        $body = $this->request('POST', '/api/notes', ['profile' => self::iri($this->bob), 'year' => 2026, 'month' => 9, 'content' => 'Hi'], self::activeProfile($this->alice));

        self::assertResponseStatusCodeSame(422);
        self::assertContains('profile', self::violationPaths($body));
    }

    public function testWriteWithoutActiveProfileIsRejected(): void
    {
        $this->request('POST', '/api/incomes', ['profile' => self::iri($this->alice), 'year' => 2026, 'month' => 9, 'netPay' => '1000.00']);

        self::assertResponseStatusCodeSame(400);
    }
}
