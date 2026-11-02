<?php

namespace App\Factory;

use App\Entity\Log;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Log>
 */
final class LogFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Log::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     */
    protected function defaults(): array
    {
        return [
            'country' => self::faker()->text(128),
            'createdAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'device' => self::faker()->text(64),
            'ipAddress' => self::faker()->text(64),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Log $log): void {})
        ;
    }
}
