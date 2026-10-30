<?php

namespace App\Tests\Api;

use App\Entity\Profile;
use PHPUnit\Framework\Attributes\DataProvider;

final class ProfileTest extends ApiTestCase
{
    private function createProfile(string $name): Profile
    {
        $profile = (new Profile())->setName($name);
        $this->entityManager()->persist($profile);
        $this->entityManager()->flush();

        return $profile;
    }

    public function testDeletingTheLastProfileIsRejected(): void
    {
        $profile = $this->createProfile('Alice');

        $body = $this->request('DELETE', '/api/profiles/'.$profile->getId());

        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('last remaining profile', $body['violations'][0]['message'] ?? '');
        self::assertSame(1, $this->entityManager()->getRepository(Profile::class)->count());
    }

    public function testDeletingAProfileWhenOthersRemainSucceeds(): void
    {
        $profile = $this->createProfile('Alice');
        $this->createProfile('Bob');

        $this->request('DELETE', '/api/profiles/'.$profile->getId());

        self::assertResponseStatusCodeSame(204);
        self::assertSame(1, $this->entityManager()->getRepository(Profile::class)->count());
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function validProfiles(): iterable
    {
        yield 'with color' => [['name' => 'Alice', 'color' => '#8b5cf6']];
        yield 'uppercase color' => [['name' => 'Alice', 'color' => '#8B5CF6']];
        yield 'null color' => [['name' => 'Alice', 'color' => null]];
        yield 'no color' => [['name' => 'Alice']];
        yield '255-char name' => [['name' => str_repeat('a', 255)]];
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[DataProvider('validProfiles')]
    public function testCreateValidProfile(array $payload): void
    {
        $body = $this->request('POST', '/api/profiles', $payload);

        self::assertResponseStatusCodeSame(201);
        self::assertSame($payload['name'], $body['name']);
        self::assertSame($payload['color'] ?? null, $body['color'] ?? null);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function invalidProfiles(): iterable
    {
        yield 'blank name' => [['name' => ''], 'name'];
        yield 'whitespace-only name' => [['name' => '   '], 'name'];
        yield 'name too long' => [['name' => str_repeat('a', 256)], 'name'];
        yield 'short hex color' => [['name' => 'Alice', 'color' => '#fff'], 'color'];
        yield 'named color' => [['name' => 'Alice', 'color' => 'red'], 'color'];
        yield 'color without hash' => [['name' => 'Alice', 'color' => '8b5cf6'], 'color'];
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[DataProvider('invalidProfiles')]
    public function testCreateInvalidProfileIsRejected(array $payload, string $invalidField): void
    {
        $body = $this->request('POST', '/api/profiles', $payload);

        self::assertResponseStatusCodeSame(422);
        self::assertContains($invalidField, self::violationPaths($body));
    }

    public function testUpdateWithInvalidColorIsRejected(): void
    {
        $profile = $this->createProfile('Alice');

        $body = $this->request('PATCH', '/api/profiles/'.$profile->getId(), ['color' => 'not-a-color']);

        self::assertResponseStatusCodeSame(422);
        self::assertContains('color', self::violationPaths($body));
    }
}
