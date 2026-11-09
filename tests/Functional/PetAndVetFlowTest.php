<?php

namespace App\Tests\Functional;

use App\Entity\User;

/** An owner's pets (create → list → edit → delete), assigning a vet, nearby vets. */
class PetAndVetFlowTest extends ApiTestCase
{
    public function testOwnerManagesTheirPets(): void
    {
        $owner = $this->owner();
        $pet = ['name' => 'Rex', 'animal' => 'Dog', 'breed' => 'mix', 'dateOfBirth' => '2020-01-01T00:00', 'owner' => $owner->getId()];

        [$status, $created] = $this->api($owner, 'POST', '/pet', $pet);
        self::assertSame(201, $status);

        [, $mine] = $this->api($owner, 'GET', '/my_pets?page=1&limit=10');
        self::assertSame([$created['id']], self::ids($mine['items']));

        [$status, $edited] = $this->api($owner, 'PUT', '/pet/' . $created['id'], ['name' => 'Rex II'] + $pet);
        self::assertSame(201, $status);
        self::assertSame('Rex II', $edited['name']);

        self::assertSame(204, $this->api($owner, 'DELETE', '/pet/' . $created['id'])[0]);
        [, $mine] = $this->api($owner, 'GET', '/my_pets?page=1&limit=10');
        self::assertSame([], $mine['items']);
    }

    public function testOwnerCannotCreateAPetForSomeoneElse(): void
    {
        $owner = $this->owner();
        $other = $this->owner();

        [$status] = $this->api($owner, 'POST', '/pet', ['name' => 'Rex', 'animal' => 'Dog', 'breed' => 'mix', 'dateOfBirth' => '2020-01-01T00:00', 'owner' => $other->getId()]);
        self::assertSame(403, $status);
    }

    public function testAssignAVetAndTheVetSeesThePet(): void
    {
        $owner = $this->owner();
        $vet = $this->vet();
        $pet = $this->pet($owner);

        self::assertSame(204, $this->api($owner, 'POST', '/assign', ['vet' => $vet->getId()])[0]);

        [$status, $vetPets] = $this->api($vet, 'GET', '/vets/' . $vet->getId() . '/pets');
        self::assertSame(200, $status);
        self::assertContains($pet->getId(), self::ids($vetPets));
    }

    public function testNearbyVets(): void
    {
        $belgrade = $this->vet(['latitude' => '44.8125', 'longitude' => '20.4612']);
        $noviSad = $this->vet(['latitude' => '45.2671', 'longitude' => '19.8335']); // ~70 km away

        [$status, $body] = $this->api($this->owner(), 'GET', '/vets/nearby?latitude=44.80&longitude=20.47&distance=10');
        self::assertSame(200, $status);
        $emails = self::ids($body, 'email');
        self::assertContains($belgrade->getEmail(), $emails);
        self::assertNotContains($noviSad->getEmail(), $emails);

        self::assertSame(400, $this->api($this->owner(), 'GET', '/vets/nearby?latitude=44.8')[0], 'missing parameters were a 500');
    }

    public function testRegisteringATakenEmailIsAValidationError(): void
    {
        $existing = $this->owner();

        [$status, $body] = $this->api(null, 'POST', '/user', [
            'email' => $existing->getEmail(), 'firstName' => 'A', 'lastName' => 'B', 'password' => 'Secret-Pass-1', 'phone' => '06',
        ]);

        self::assertSame(422, $status, 'was a 500 from the unique index');
        self::assertContains('email', array_column($body['errors'], 'field'));
        self::assertSame(1, static::getContainer()->get('doctrine')->getRepository(User::class)->count(['email' => $existing->getEmail()]));
    }
}
