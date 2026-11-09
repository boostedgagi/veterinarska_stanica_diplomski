<?php

namespace App\Tests\Unit\Security;

use App\Entity\HealthRecord;
use App\Entity\Pet;
use App\Entity\User;
use App\Factory\ExaminationFactory;
use App\Factory\PetFactory;
use App\Factory\UserFactory;
use DateTime;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use function Zenstruck\Foundry\Persistence\persistent_factory;

/**
 * Ownership rules (src/Security/Voter): who may see / change which user, pet and health record.
 * Each rule is checked with one allowed and one forbidden caller.
 */
class OwnershipTest extends WebTestCase
{
    use ResetDatabase;
    use Factories;

    private KernelBrowser $client;
    private User $owner;
    private User $stranger;
    private User $vet;        // the owner's assigned vet, and the vet of the health record
    private User $otherVet;
    private User $admin;
    private Pet $pet;
    private HealthRecord $healthRecord;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $active = ['allowed' => true, 'verified' => true, 'roles' => []];
        $this->vet = UserFactory::createOne($active + ['typeOfUser' => User::TYPE_VET]);
        $this->otherVet = UserFactory::createOne($active + ['typeOfUser' => User::TYPE_VET]);
        $this->owner = UserFactory::createOne($active + ['typeOfUser' => User::TYPE_USER, 'vet' => $this->vet]);
        $this->stranger = UserFactory::createOne($active + ['typeOfUser' => User::TYPE_USER]);
        $this->admin = UserFactory::createOne(['allowed' => true, 'verified' => true, 'roles' => ['ROLE_ADMIN'], 'typeOfUser' => User::TYPE_ADMIN]);
        $this->pet = PetFactory::createOne(['owner' => $this->owner]);
        $this->healthRecord = persistent_factory(HealthRecord::class)->create([
            'vet' => $this->vet,
            'pet' => $this->pet,
            'examination' => ExaminationFactory::createOne(),
            'startedAt' => new DateTime('2027-03-01 10:00'),
            'finishedAt' => new DateTime('2027-03-01 11:00'),
            'status' => 'waiting',
            'madeByVet' => false,
        ]);
    }

    /** @param array<string, mixed>|null $json */
    private function callAs(User $as, string $method, string $uri, ?array $json = null): int
    {
        // a fresh client per call: loginUser() can't switch users on one stateless client
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->client->loginUser($as);
        $this->client->request($method, $uri, server: ['CONTENT_TYPE' => 'application/json'], content: $json === null ? null : json_encode($json));

        return $this->client->getResponse()->getStatusCode();
    }

    /** @return array<string, mixed> */
    private function petBody(User $owner): array
    {
        return ['name' => 'Rex', 'animal' => 'Dog', 'breed' => 'mix', 'dateOfBirth' => '2020-01-01T00:00', 'owner' => $owner->getId()];
    }

    /** @return array<string, mixed> */
    private function healthRecordBody(Pet $pet): array
    {
        return [
            'vet' => $this->vet->getId(), 'pet' => $pet->getId(), 'examination' => $this->healthRecord->getExamination()->getId(),
            'startedAt' => '2027-03-02T10:00:00.000Z', 'finishedAt' => '2027-03-02T11:00:00.000Z', 'status' => 'waiting',
        ];
    }

    public function testPetView(): void
    {
        $uri = '/pet/' . $this->pet->getId();
        self::assertSame(200, $this->callAs($this->owner, 'GET', $uri));
        self::assertSame(200, $this->callAs($this->vet, 'GET', $uri), 'the owner\'s vet');
        self::assertSame(200, $this->callAs($this->admin, 'GET', $uri));
        self::assertSame(403, $this->callAs($this->stranger, 'GET', $uri));
        self::assertSame(403, $this->callAs($this->otherVet, 'GET', $uri), 'a vet who never treated the pet');
        self::assertSame(403, $this->callAs($this->stranger, 'GET', $uri . '/health_record'));
        self::assertSame(403, $this->callAs($this->stranger, 'GET', '/user/' . $this->owner->getId() . '/pets'));
    }

    public function testPetChange(): void
    {
        $uri = '/pet/' . $this->pet->getId();
        self::assertSame(403, $this->callAs($this->stranger, 'PUT', $uri, $this->petBody($this->owner)));
        self::assertSame(403, $this->callAs($this->vet, 'PUT', $uri, $this->petBody($this->owner)), 'vets may view, not edit');
        self::assertSame(403, $this->callAs($this->owner, 'PUT', $uri, $this->petBody($this->stranger)), 'giving the pet away');
        self::assertSame(201, $this->callAs($this->owner, 'PUT', $uri, $this->petBody($this->owner)));
        self::assertSame(403, $this->callAs($this->stranger, 'POST', '/pet_upload_image/' . $this->pet->getId()));
        self::assertSame(403, $this->callAs($this->stranger, 'DELETE', $uri));
        self::assertSame(204, $this->callAs($this->owner, 'DELETE', $uri));
    }

    public function testPetCreate(): void
    {
        self::assertSame(403, $this->callAs($this->stranger, 'POST', '/pet', $this->petBody($this->owner)), 'pet for someone else');
        self::assertSame(201, $this->callAs($this->stranger, 'POST', '/pet', $this->petBody($this->stranger)));
        self::assertSame(201, $this->callAs($this->admin, 'POST', '/pet', $this->petBody($this->owner)));
    }

    public function testHealthRecordAccess(): void
    {
        $uri = '/health_record/' . $this->healthRecord->getId();
        self::assertSame(200, $this->callAs($this->owner, 'GET', $uri));
        self::assertSame(200, $this->callAs($this->vet, 'GET', $uri));
        self::assertSame(403, $this->callAs($this->stranger, 'GET', $uri));
        self::assertSame(403, $this->callAs($this->otherVet, 'GET', $uri));

        $strangersPet = PetFactory::createOne(['owner' => $this->stranger]);
        self::assertSame(403, $this->callAs($this->stranger, 'PUT', $uri, $this->healthRecordBody($strangersPet)));
        self::assertSame(403, $this->callAs($this->owner, 'PUT', $uri, $this->healthRecordBody($strangersPet)), 'moving it to someone else\'s pet');
        self::assertSame(201, $this->callAs($this->owner, 'PUT', $uri, $this->healthRecordBody($this->pet)));

        self::assertSame(403, $this->callAs($this->owner, 'DELETE', $uri), 'owners cancel, they do not delete');
        self::assertSame(204, $this->callAs($this->vet, 'DELETE', $uri));
    }

    public function testHealthRecordCreate(): void
    {
        self::assertSame(403, $this->callAs($this->stranger, 'POST', '/health_record', $this->healthRecordBody($this->pet)), 'booking on someone else\'s pet');
        self::assertSame(201, $this->callAs($this->owner, 'POST', '/health_record', $this->healthRecordBody($this->pet)));
    }

    public function testUserAccess(): void
    {
        $uri = '/user/' . $this->owner->getId();
        self::assertSame(200, $this->callAs($this->owner, 'GET', $uri));
        self::assertSame(200, $this->callAs($this->vet, 'GET', $uri));
        self::assertSame(403, $this->callAs($this->stranger, 'GET', $uri));
        self::assertSame(403, $this->callAs($this->stranger, 'GET', $uri . '/health_records'));
        self::assertSame(403, $this->callAs($this->stranger, 'POST', $uri . '/upload_image'));
        self::assertSame(403, $this->callAs($this->stranger, 'POST', $uri . '/remove_image'));

        self::assertSame(403, $this->callAs($this->owner, 'PATCH', $uri, ['verified' => true]), 'admin form: users use PATCH /user');
        // the endpoint currently needs the full object (missing fields are cleared)
        self::assertSame(201, $this->callAs($this->admin, 'PATCH', $uri, [
            'email' => $this->owner->getEmail(), 'firstName' => 'Renamed', 'lastName' => $this->owner->getLastName(),
            'phone' => $this->owner->getPhone(), 'allowed' => true, 'verified' => true,
        ]));

        self::assertSame(403, $this->callAs($this->stranger, 'DELETE', $uri));
        self::assertSame(204, $this->callAs($this->owner, 'DELETE', $uri));
    }

    public function testVetOwnData(): void
    {
        foreach (['/vets/%d/pets', '/vet/%d/health_record?page=1&limit=5'] as $pattern) {
            $uri = sprintf($pattern, $this->vet->getId());
            self::assertSame(200, $this->callAs($this->vet, 'GET', $uri), $uri);
            self::assertSame(200, $this->callAs($this->admin, 'GET', $uri), $uri);
            self::assertSame(403, $this->callAs($this->otherVet, 'GET', $uri), $uri);
        }
    }
}
