<?php

namespace App\Factory;

use App\Entity\Pet;
use App\Repository\PetRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Pet>
 *
 * @method        Pet|Proxy create(array|callable $attributes = [])
 * @method static Pet|Proxy createOne(array $attributes = [])
 * @method static Pet|Proxy find(object|array|mixed $criteria)
 * @method static Pet|Proxy findOrCreate(array $attributes)
 * @method static Pet|Proxy first(string $sortedField = 'id')
 * @method static Pet|Proxy last(string $sortedField = 'id')
 * @method static Pet|Proxy random(array $attributes = [])
 * @method static Pet|Proxy randomOrCreate(array $attributes = [])
 * @method static PetRepository|RepositoryProxy repository()
 * @method static Pet[]|Proxy[] all()
 * @method static Pet[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static Pet[]|Proxy[] createSequence(array|callable $sequence)
 * @method static Pet[]|Proxy[] findBy(array $attributes)
 * @method static Pet[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static Pet[]|Proxy[] randomSet(int $number, array $attributes = [])
 */
final class PetFactory extends ModelFactory
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
            'animal' => 'dog',
            'breed' => self::faker()->lastName(),
//            'createdAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'dateOfBirth' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'name' => self::faker()->firstNameMale(255),
            'owner' => UserFactory::random(), // TODO add App\Entity\User ORM type manually
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(Pet $pet): void {})
        ;
    }

    protected static function getClass(): string
    {
        return Pet::class;
    }
}
