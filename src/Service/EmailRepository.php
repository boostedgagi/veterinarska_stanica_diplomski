<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailRepository
{
    public function sendWelcomeEmail(User $user,MailerInterface $mailer):void
    {
        $email = (new Email())
            ->from('welcome@vetshop.com')
            ->to($user->getEmail())
            ->subject('Welcome to the vetShop')
            ->text("Hi {$user->getFirstName()}!\nWe are very glad that you are our new member!");

        $mailer->send($email);
    }
}