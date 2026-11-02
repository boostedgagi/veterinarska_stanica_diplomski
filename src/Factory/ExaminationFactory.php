<?php

namespace App\Factory;

use App\Entity\Examination;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Examination>
 */
final class ExaminationFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Examination::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     */
    protected function defaults(): array
    {
        return [
//            'createdAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'duration' => self::faker()->numberBetween(5,100),
            'name' => self::faker()->text(20),
            'price' => self::faker()->numberBetween(50,4500),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Examination $examination): void {})
        ;
    }
}
