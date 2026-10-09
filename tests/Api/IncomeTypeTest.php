<?php

namespace App\Tests\Api;

use App\Entity\Income;
use App\Entity\IncomeType;
use App\Entity\Profile;
use App\Repository\IncomeTypeRepository;

/**
 * Covers the management of income types: creation at the end of the list,
 * reorder, deletion of a used type.
 */
final class IncomeTypeTest extends ApiTestCase
{
    private Profile $alice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->alice = $this->persist((new Profile())->setName('Alice'));
    }

    /**
     * @param array<string, mixed>|null $body
     *
     * @return array<string, mixed>|null
     */
    private function call(string $method, string $uri, ?array $body = null): ?array
    {
        return $this->request($method, $uri, $body, self::activeProfile($this->alice));
    }

    private static function iri(IncomeType $type): string
    {
        return '/api/income_types/'.$type->getId();
    }

    /**
     * @return list<string> IRIs of every type, in display order
     */
    private function listedIris(): array
    {
        return array_column($this->call('GET', '/api/income_types')['member'], '@id');
    }

    public function testNewTypeGoesAtTheEnd(): void
    {
        $max = static::getContainer()->get(IncomeTypeRepository::class)->nextSortOrder();

        $body = $this->call('POST', '/api/income_types', ['name' => 'Prime', 'color' => '#3B82F6', 'sortOrder' => -5]);

        self::assertResponseStatusCodeSame(201);
        self::assertSame($max, $body['sortOrder']);
        self::assertSame($body['@id'], array_last($this->listedIris()));
    }

    public function testNameIsRequired(): void
    {
        $body = $this->call('POST', '/api/income_types', ['name' => '  ']);

        self::assertResponseStatusCodeSame(422);
        self::assertContains('name', self::violationPaths($body));
    }

    public function testRename(): void
    {
        $type = $this->call('POST', '/api/income_types', ['name' => 'Prime']);

        $body = $this->call('PATCH', $type['@id'], ['name' => 'Prime annuelle', 'color' => '#22C55E']);

        self::assertResponseIsSuccessful();
        self::assertSame('Prime annuelle', $body['name']);
        self::assertSame('#22C55E', $body['color']);
    }

    public function testReorder(): void
    {
        $order = array_reverse($this->listedIris());

        $this->call('PATCH', '/api/income_types/reorder', ['order' => $order]);

        self::assertResponseStatusCodeSame(204);
        self::assertSame($order, $this->listedIris());
    }

    public function testReorderMustListEveryType(): void
    {
        $iris = $this->listedIris();
        self::assertGreaterThan(1, \count($iris), 'seeded types expected');

        $body = $this->call('PATCH', '/api/income_types/reorder', ['order' => \array_slice($iris, 1)]);
        self::assertResponseStatusCodeSame(422);
        self::assertContains('order', self::violationPaths($body));

        $this->call('PATCH', '/api/income_types/reorder', ['order' => [...$iris, $iris[0]]]);
        self::assertResponseStatusCodeSame(422);
    }

    public function testUnusedTypeCanBeDeleted(): void
    {
        $type = $this->call('POST', '/api/income_types', ['name' => 'Prime']);

        $this->call('DELETE', $type['@id']);

        self::assertResponseStatusCodeSame(204);
    }

    public function testUsedTypeCannotBeDeleted(): void
    {
        $type = $this->persist((new IncomeType())->setName('Prime')->setSortOrder(99));
        $this->persist((new Income())->setProfile($this->alice)->setYear(2026)->setMonth(1)->setType($type)->setAmount('100.00'));

        $body = $this->call('DELETE', self::iri($type));

        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('used by 1 income', $body['violations'][0]['message']);
    }
}
