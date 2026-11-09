<?php

namespace App\RateLimit;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;

/**
 * Lexik answers every failed login with 401; a throttled one gets 429 so clients can
 * tell "wrong password" from "wait".
 */
#[AsEventListener(Events::AUTHENTICATION_FAILURE)]
final class ThrottledLoginListener
{
    public function __invoke(AuthenticationFailureEvent $event): void
    {
        $exception = $event->getException();
        if (!$exception instanceof TooManyLoginAttemptsAuthenticationException) {
            return;
        }

        $event->setResponse(new JsonResponse(
            ['code' => Response::HTTP_TOO_MANY_REQUESTS, 'message' => strtr($exception->getMessageKey(), $exception->getMessageData())],
            Response::HTTP_TOO_MANY_REQUESTS,
        ));
    }
}
