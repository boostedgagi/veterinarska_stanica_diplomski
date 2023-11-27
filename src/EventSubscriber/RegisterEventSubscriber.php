<?php

namespace App\EventSubscriber;

use App\Entity\Token;
use App\Event\UserRegisterEvent;
use App\Service\TemplatedEmail;
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
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }

    public function onKernelController():void
    {
        // ...
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function onUserRegistration(UserRegisterEvent $event): void
    {
        $token = new Token();

        $event->em->persist($token);
        $event->em->flush();

        $email = new TemplatedEmail($event->mailer);
        $email->sendWelcomeEmail($event->user, $token);
    }
}
