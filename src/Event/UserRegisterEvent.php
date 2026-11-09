<?php

namespace App\Event;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;

class UserRegisterEvent
{
    public const NAME = 'user.register';

    public function __construct(public User $user, public MailerInterface $mailer, public EntityManagerInterface $em)
    {
    }
}