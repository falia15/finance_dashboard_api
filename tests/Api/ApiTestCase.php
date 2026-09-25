<?php

namespace App\Tests\Api;

use App\Entity\Profile;
use App\Http\ActiveProfileResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Base class for functional API tests.
 *
 * api-platform/test is not compatible with PHPUnit 13 yet, so this wraps
 * Symfony's KernelBrowser with JSON-LD helpers instead. Each test runs in a
 * transaction rolled back by dama/doctrine-test-bundle.
 */
abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * @param array<string, mixed>|null $body
     * @param array<string, string>     $headers extra headers, e.g. ['X-Profile-Id' => '1']
     *
     * @return array<string, mixed>|null decoded response body
     */
    protected function request(string $method, string $uri, ?array $body = null, array $headers = []): ?array
    {
        $contentType = 'PATCH' === $method ? 'application/merge-patch+json' : 'application/ld+json';
        $server = ['HTTP_ACCEPT' => 'application/ld+json', 'CONTENT_TYPE' => $contentType];
        foreach ($headers as $name => $value) {
            $server['HTTP_'.strtoupper(str_replace('-', '_', $name))] = $value;
        }

        $this->client->request($method, $uri, server: $server, content: null === $body ? null : json_encode($body, \JSON_THROW_ON_ERROR));

        $content = $this->client->getResponse()->getContent();

        return '' === $content || false === $content ? null : json_decode($content, true, flags: \JSON_THROW_ON_ERROR);
    }

    protected function entityManager(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    /**
     * @template T of object
     *
     * @param T $entity
     *
     * @return T
     */
    protected function persist(object $entity): object
    {
        $this->entityManager()->persist($entity);
        $this->entityManager()->flush();

        return $entity;
    }

    /**
     * @return array<string, string> headers making the given profile the active one
     */
    protected static function activeProfile(Profile $profile): array
    {
        return [ActiveProfileResolver::HEADER => (string) $profile->getId()];
    }

    /**
     * @param array<string, mixed>|null $body decoded 422 response
     *
     * @return list<string> property paths of the violations
     */
    protected static function violationPaths(?array $body): array
    {
        return array_column($body['violations'] ?? [], 'propertyPath');
    }
}
