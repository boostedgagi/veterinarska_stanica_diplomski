<?php

namespace App\Factory;

use App\Entity\HealthRecord;
use App\Repository\HealthRecordRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<HealthRecord>
 *
 * @method        HealthRecord|Proxy create(array|callable $attributes = [])
 * @method static HealthRecord|Proxy createOne(array $attributes = [])
 * @method static HealthRecord|Proxy find(object|array|mixed $criteria)
 * @method static HealthRecord|Proxy findOrCreate(array $attributes)
 * @method static HealthRecord|Proxy first(string $sortedField = 'id')
 * @method static HealthRecord|Proxy last(string $sortedField = 'id')
 * @method static HealthRecord|Proxy random(array $attributes = [])
 * @method static HealthRecord|Proxy randomOrCreate(array $attributes = [])
 * @method static HealthRecordRepository|RepositoryProxy repository()
 * @method static HealthRecord[]|Proxy[] all()
 * @method static HealthRecord[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static HealthRecord[]|Proxy[] createSequence(array|callable $sequence)
 * @method static HealthRecord[]|Proxy[] findBy(array $attributes)
 * @method static HealthRecord[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static HealthRecord[]|Proxy[] randomSet(int $number, array $attributes = [])
 */
final class HealthRecordFactory extends ModelFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function getDefaults(): array
    {
        return [
            'examination' => ExaminationFactory::random(), // TODO add App\Entity\Examination ORM type manually
            'startedAt' => new \DateTime(), // TODO add DATETIME ORM type manually
            'finishedAt' => new \DateTime('+1 hour'), // TODO add DATETIME ORM type manually
            'pet' => PetFactory::random(), // TODO add App\Entity\Pet ORM type manually
            'status' => self::faker()->text(64),
            'vet' => UserFactory::random(), // TODO add App\Entity\User ORM type manually
            'notified' => 0
//            'createdAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(HealthRecord $healthRecord): void {})
        ;
    }

    protected static function getClass(): string
    {
        return HealthRecord::class;
    }
}
