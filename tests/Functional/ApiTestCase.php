<?php

namespace App\Tests\Functional;

use App\Entity\HealthRecord;
use App\Entity\Pet;
use App\Entity\User;
use App\Factory\ExaminationFactory;
use App\Factory\PetFactory;
use App\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use function Zenstruck\Foundry\Persistence\persistent_factory;

/**
 * Base for the flow tests: real HTTP requests through the kernel, one fresh client per call
 * (loginUser() can't switch users on one stateless client).
 */
abstract class ApiTestCase extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * @param array<string, mixed>|null $json
     * @return array{int, mixed} status and decoded JSON body (null when empty)
     */
    protected function api(?User $as, string $method, string $uri, ?array $json = null): array
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        if ($as) {
            $this->client->loginUser($as);
        }
        $this->client->request($method, $uri, server: ['CONTENT_TYPE' => 'application/json'], content: $json === null ? null : json_encode($json));
        $response = $this->client->getResponse();

        return [$response->getStatusCode(), json_decode((string) $response->getContent(), true)];
    }

    protected function owner(): User
    {
        return UserFactory::createOne(['typeOfUser' => User::TYPE_USER, 'roles' => [], 'allowed' => true, 'verified' => true]);
    }

    /** @param array<string, mixed> $attributes */
    protected function vet(array $attributes = []): User
    {
        return UserFactory::createOne(['typeOfUser' => User::TYPE_VET, 'roles' => ['ROLE_VET'], 'allowed' => true, 'verified' => true] + $attributes);
    }

    protected function pet(User $owner): Pet
    {
        return PetFactory::createOne(['owner' => $owner]);
    }

    /** @param array<string, mixed> $attributes */
    protected function appointment(User $vet, Pet $pet, string $from, string $to, array $attributes = []): HealthRecord
    {
        return persistent_factory(HealthRecord::class)->create($attributes + [
            'vet' => $vet, 'pet' => $pet, 'examination' => ExaminationFactory::createOne(),
            'startedAt' => new \DateTime($from), 'finishedAt' => new \DateTime($to),
            'status' => 'waiting', 'madeByVet' => false,
        ]);
    }

    protected function em(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T|null
     */
    protected function reload(string $class, ?int $id): ?object
    {
        $this->em()->clear();

        return $this->em()->find($class, $id);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return list<mixed>
     */
    protected static function ids(array $rows, string $key = 'id'): array
    {
        return array_column($rows, $key);
    }
}
