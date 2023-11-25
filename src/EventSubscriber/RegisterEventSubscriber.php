<?php

namespace App\EventSubscriber;

use App\Entity\Token;
use App\Entity\User;
use App\Model\Token as ModelToken;
use App\Event\UserRegisterEvent;
use App\Service\TemplatedEmail;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

class RegisterEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly MailerInterface        $mailer,
        private readonly User                   $user,
        private readonly EntityManagerInterface $em
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserRegisterEvent::NAME => 'onUserRegistration',
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        // ...
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function onUserRegistration(UserRegisterEvent $event): void
    {
        //here goes some code for for example sending mails with token or sth like that
        $email = new TemplatedEmail($this->mailer);
//
        $token30minutes = (new ModelToken())->make30MinToken();
        $token = new Token($token30minutes);

        $this->em->persist($token);
        $this->em->flush();

        dd($this->user);
        $email->sendWelcomeEmail($this->user, $token);
    }
}
