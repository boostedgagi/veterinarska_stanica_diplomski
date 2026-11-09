<?php

namespace App\Tests\Unit\Controller;

use App\Entity\HealthRecord;
use App\Entity\User;
use App\Factory\ExaminationFactory;
use App\Repository\UserRepository;
use App\Factory\PetFactory;
use App\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use function Zenstruck\Foundry\Persistence\persistent_factory;

/**
 * Requests that used to answer 500 (or 200 with an error message) get a proper status,
 * and PATCH only changes the fields that are sent.
 */
class ApiErrorResponsesTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    private KernelBrowser $client;
    private User $admin;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->admin = UserFactory::createOne(['roles' => ['ROLE_ADMIN'], 'typeOfUser' => User::TYPE_ADMIN, 'allowed' => true, 'verified' => true]);
    }

    /** @param array<string, mixed>|null $json */
    private function call(?User $as, string $method, string $uri, ?array $json = null): int
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        if ($as) {
            $this->client->loginUser($as);
        }
        $this->client->request($method, $uri, server: ['CONTENT_TYPE' => 'application/json'], content: $json === null ? null : json_encode($json));

        return $this->client->getResponse()->getStatusCode();
    }

    public function testFoundPet(): void
    {
        $pet = PetFactory::createOne(['owner' => UserFactory::createOne(['typeOfUser' => User::TYPE_USER])]);

        self::assertSame(200, $this->call(null, 'GET', '/found_pet?id=' . $pet->getId()));
        $body = json_decode((string) $this->client->getResponse()->getContent(), true);
        // what the frontend's found-pet page (QR code) shows; nothing else about the owner
        self::assertEqualsCanonicalizing(['name', 'dateOfBirth', 'animal', 'breed', 'image', 'owner'], array_keys($body));
        self::assertEqualsCanonicalizing(['firstName', 'email', 'phone'], array_keys($body['owner']));
        self::assertSame(404, $this->call(null, 'GET', '/found_pet?id=999999'), 'was 500');
        self::assertSame(400, $this->call(null, 'GET', '/found_pet'), 'was 500');
    }

    public function testUnknownIdsAnswer404(): void
    {
        foreach ([
            ['GET', '/health_record/999999'], ['PUT', '/health_record/999999'], ['DELETE', '/health_record/999999'],
            ['GET', '/user/999999/health_records'], ['GET', '/user/999999'],
            ['PUT', '/pet/999999'], ['DELETE', '/pet/999999'], ['POST', '/pet_upload_image/999999'], ['GET', '/pet/999999'],
        ] as [$method, $uri]) {
            self::assertSame(404, $this->call($this->admin, $method, $uri, $method === 'PUT' ? [] : null), "$method $uri");
        }
    }

    public function testPaginationDefaults(): void
    {
        $vet = UserFactory::createOne(['typeOfUser' => User::TYPE_VET, 'allowed' => true, 'verified' => true]);

        self::assertSame(200, $this->call($vet, 'GET', '/vet/' . $vet->getId() . '/health_record'), 'no ?page: was 500');
        self::assertSame(200, $this->call($vet, 'GET', '/vet/' . $vet->getId() . '/health_record?page=0&limit=-5'));
    }

    public function testPatchOwnProfileChangesOnlySentFields(): void
    {
        $user = UserFactory::createOne(['typeOfUser' => User::TYPE_USER, 'allowed' => true, 'verified' => true]);
        $email = $user->getEmail();
        $passwordHash = $user->getPassword();

        self::assertSame(201, $this->call($user, 'PATCH', '/user', ['firstName' => 'Renamed', 'password' => 'plain-text']));
        $body = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame('Renamed', $body['firstName']);
        self::assertSame($email, $body['email'], 'missing fields were cleared before');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $reloaded = $em->find(User::class, $user->getId());
        self::assertSame('Renamed', $reloaded->getFirstName(), 'saved');
        self::assertSame($passwordHash, $reloaded->getPassword(), 'password hash kept');
    }

    public function testAdminPatchChangesOnlySentFields(): void
    {
        $user = UserFactory::createOne(['typeOfUser' => User::TYPE_USER, 'allowed' => true, 'verified' => false]);
        $email = $user->getEmail();

        self::assertSame(201, $this->call($this->admin, 'PATCH', '/user/' . $user->getId(), ['verified' => true]), 'partial body was 500');

        $reloaded = static::getContainer()->get(EntityManagerInterface::class)->find(User::class, $user->getId());
        self::assertTrue($reloaded->isVerified());
        self::assertSame($email, $reloaded->getEmail());
    }

    public function testHealthRecordStillLoadsAfterItsVetIsDeleted(): void
    {
        $owner = UserFactory::createOne(['typeOfUser' => User::TYPE_USER, 'allowed' => true, 'verified' => true]);
        $vet = UserFactory::createOne(['typeOfUser' => User::TYPE_VET, 'allowed' => true, 'verified' => true]);
        $record = persistent_factory(HealthRecord::class)->create([
            'vet' => $vet, 'pet' => PetFactory::createOne(['owner' => $owner]), 'examination' => ExaminationFactory::createOne(),
            'startedAt' => new \DateTime('2027-03-01 10:00'), 'finishedAt' => new \DateTime('2027-03-01 11:00'),
            'status' => 'waiting', 'madeByVet' => false,
        ]);

        self::assertSame(204, $this->call($this->admin, 'DELETE', '/user/' . $vet->getId()));
        // vet_id is now NULL (ON DELETE SET NULL); the non-nullable property made loading it a TypeError
        self::assertSame(200, $this->call($owner, 'GET', '/health_record/' . $record->getId()));
    }

    public function testFindByRoleReturnsNullWhenNobodyHasTheRole(): void
    {
        $repo = static::getContainer()->get(UserRepository::class);

        self::assertNull($repo->findByRole('ROLE_NOBODY_HAS_THIS'), 'was an undefined index / TypeError');
        self::assertSame($this->admin->getId(), $repo->findByRole('ROLE_ADMIN')?->getId());
    }
}
