<?php

namespace App\EventSubscriber;

use App\Entity\Token;
use App\Event\UserRegisterEvent;
use App\Factory\TokenFactory;
use App\Service\TemplatedEmailService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class RegisterEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            UserRegisterEvent::NAME => 'onUserRegistration',
        ];
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function onUserRegistration(UserRegisterEvent $event): void
    {
        $tokenFactory = new TokenFactory($event->em);
        $token = $tokenFactory->save();

        $event->em->persist($token);
        $event->em->flush();

        $email = new TemplatedEmailService($event->mailer);
        $email->sendWelcomeEmail($event->user, $token);
    }
}
