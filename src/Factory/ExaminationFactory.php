<?php

namespace App\Factory;

use App\Entity\Examination;
use App\Repository\ExaminationRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Examination>
 *
 * @method        Examination|Proxy create(array|callable $attributes = [])
 * @method static Examination|Proxy createOne(array $attributes = [])
 * @method static Examination|Proxy find(object|array|mixed $criteria)
 * @method static Examination|Proxy findOrCreate(array $attributes)
 * @method static Examination|Proxy first(string $sortedField = 'id')
 * @method static Examination|Proxy last(string $sortedField = 'id')
 * @method static Examination|Proxy random(array $attributes = [])
 * @method static Examination|Proxy randomOrCreate(array $attributes = [])
 * @method static ExaminationRepository|RepositoryProxy repository()
 * @method static Examination[]|Proxy[] all()
 * @method static Examination[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static Examination[]|Proxy[] createSequence(array|callable $sequence)
 * @method static Examination[]|Proxy[] findBy(array $attributes)
 * @method static Examination[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static Examination[]|Proxy[] randomSet(int $number, array $attributes = [])
 */
final class ExaminationFactory extends ModelFactory
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
//            'createdAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'duration' => self::faker()->numberBetween(5,100),
            'name' => self::faker()->text(20),
            'price' => self::faker()->numberBetween(50,4500),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(Examination $examination): void {})
        ;
    }

    protected static function getClass(): string
    {
        return Examination::class;
    }
}
