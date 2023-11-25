<?php

namespace App\Event;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;

class UserRegisterEvent
{
    public const NAME = 'user.register';

    public User $user;

    public MailerInterface $mailer;

    public EntityManagerInterface $em;

    public function __construct(User $user, MailerInterface $mailer, EntityManagerInterface $em)
    {
        $this->user = $user;
        $this->mailer = $mailer;
        $this->em = $em;
    }


}