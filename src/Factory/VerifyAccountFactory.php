<?php

namespace App\Factory;

use App\Entity\Token;
use App\Repository\VerifyAccountRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Token>
 *
 * @method        Token|Proxy create(array|callable $attributes = [])
 * @method static Token|Proxy createOne(array $attributes = [])
 * @method static Token|Proxy find(object|array|mixed $criteria)
 * @method static Token|Proxy findOrCreate(array $attributes)
 * @method static Token|Proxy first(string $sortedField = 'id')
 * @method static Token|Proxy last(string $sortedField = 'id')
 * @method static Token|Proxy random(array $attributes = [])
 * @method static Token|Proxy randomOrCreate(array $attributes = [])
 * @method static VerifyAccountRepository|RepositoryProxy repository()
 * @method static Token[]|Proxy[] all()
 * @method static Token[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static Token[]|Proxy[] createSequence(array|callable $sequence)
 * @method static Token[]|Proxy[] findBy(array $attributes)
 * @method static Token[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static Token[]|Proxy[] randomSet(int $number, array $attributes = [])
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
            // ->afterInstantiate(function(Token $verifyAccount): void {})
        ;
    }

    protected static function getClass(): string
    {
        return Token::class;
    }
}
