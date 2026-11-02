<?php

namespace App\Factory;

use App\Entity\Pet;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Pet>
 */
final class PetFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Pet::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     */
    protected function defaults(): array
    {
        return [
            'animal' => 'dog',
            'breed' => self::faker()->lastName(),
            'dateOfBirth' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'name' => self::faker()->firstNameMale(255),
            'owner' => UserFactory::random(), // TODO add App\Entity\User ORM type manually,
            'image' => 'https://picsum.photos/200'
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Pet $pet): void {})
        ;
    }
}
