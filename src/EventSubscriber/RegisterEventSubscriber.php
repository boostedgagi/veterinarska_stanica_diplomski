<?php

namespace App\EventSubscriber;

use App\Event\UserRegisterEvent;
use App\Factory\TokenFactory;
use App\Service\TemplatedEmailService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class RegisterEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TokenFactory $tokenFactory,
        private readonly TemplatedEmailService $emailService,
    ) {
    }

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
        $token = $this->tokenFactory->save($event->user);
        $this->emailService->sendWelcomeEmail($event->user, $token);
    }
}
