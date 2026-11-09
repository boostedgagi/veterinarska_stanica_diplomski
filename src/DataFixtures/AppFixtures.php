<?php

namespace App\DataFixtures;

use App\Entity\HealthRecord;
use App\Entity\User;
use App\Enum\HealthRecordStatus;
use App\Factory\ExaminationFactory;
use App\Factory\PetFactory;
use App\Factory\UserFactory;
use DateInterval;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use function Zenstruck\Foundry\faker;
use function Zenstruck\Foundry\Persistence\persistent_factory;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        UserFactory::createMany(30);
        PetFactory::createMany(20);
        ExaminationFactory::createMany(6);

        // App\Factory\HealthRecordFactory is a domain helper, not a Foundry factory,
        // so health records are created with an anonymous Foundry factory.
        persistent_factory(HealthRecord::class)->many(30)->create(static function (): array {
            $startedAt = DateTime::createFromInterface(faker()->dateTimeBetween('-1 month', '+1 month'));

            return [
                'vet' => UserFactory::randomOrCreate(['typeOfUser' => User::TYPE_VET]),
                'pet' => PetFactory::random(),
                'examination' => ExaminationFactory::random(),
                'startedAt' => $startedAt,
                'finishedAt' => (clone $startedAt)->add(new DateInterval('PT1H')),
                'status' => faker()->randomElement(HealthRecordStatus::cases())->value,
                'madeByVet' => faker()->boolean(),
            ];
        });
    }
}
