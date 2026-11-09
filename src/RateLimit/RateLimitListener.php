<?php

namespace App\RateLimit;

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/**
 * Applies #[RateLimited] (per client IP) before the controller runs.
 */
#[AsEventListener(KernelEvents::CONTROLLER_ARGUMENTS)]
final readonly class RateLimitListener
{
    public function __construct(
        #[AutowireLocator([
            'register' => new Autowire(service: 'limiter.register'),
            'password_request' => new Autowire(service: 'limiter.password_request'),
            'token_check' => new Autowire(service: 'limiter.token_check'),
            'contact' => new Autowire(service: 'limiter.contact'),
            'found_pet' => new Autowire(service: 'limiter.found_pet'),
        ])]
        private ContainerInterface $limiters,
    ) {
    }

    public function __invoke(ControllerArgumentsEvent $event): void
    {
        foreach ($event->getAttributes(RateLimited::class) as $attribute) {
            /** @var RateLimiterFactoryInterface $factory */
            $factory = $this->limiters->get($attribute->limiter);
            $limit = $factory->create($event->getRequest()->getClientIp() ?? 'unknown')->consume();

            if (!$limit->isAccepted()) {
                throw new TooManyRequestsHttpException(
                    max(1, $limit->getRetryAfter()->getTimestamp() - time()),
                    'Too many requests, try again later.',
                );
            }
        }
    }
}
