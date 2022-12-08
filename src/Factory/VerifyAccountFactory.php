<?php

namespace App\Factory;

use App\Entity\VerifyAccount;
use App\Repository\VerifyAccountRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<VerifyAccount>
 *
 * @method        VerifyAccount|Proxy create(array|callable $attributes = [])
 * @method static VerifyAccount|Proxy createOne(array $attributes = [])
 * @method static VerifyAccount|Proxy find(object|array|mixed $criteria)
 * @method static VerifyAccount|Proxy findOrCreate(array $attributes)
 * @method static VerifyAccount|Proxy first(string $sortedField = 'id')
 * @method static VerifyAccount|Proxy last(string $sortedField = 'id')
 * @method static VerifyAccount|Proxy random(array $attributes = [])
 * @method static VerifyAccount|Proxy randomOrCreate(array $attributes = [])
 * @method static VerifyAccountRepository|RepositoryProxy repository()
 * @method static VerifyAccount[]|Proxy[] all()
 * @method static VerifyAccount[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static VerifyAccount[]|Proxy[] createSequence(array|callable $sequence)
 * @method static VerifyAccount[]|Proxy[] findBy(array $attributes)
 * @method static VerifyAccount[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static VerifyAccount[]|Proxy[] randomSet(int $number, array $attributes = [])
 */
final class VerifyAccountFactory extends ModelFactory
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
            'expires' => self::faker()->text(255),
            'token' => self::faker()->text(255),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(VerifyAccount $verifyAccount): void {})
        ;
    }

    protected static function getClass(): string
    {
        return VerifyAccount::class;
    }
}
