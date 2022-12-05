<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailRepository
{
    public function sendWelcomeEmail(User $user,MailerInterface $mailer,object $tokenHandler):void
    {
                $email = (new Email())
            ->from('welcome@vetshop.com')
            ->to($user->getEmail())
            ->subject('Welcome to the vetShop')
            ->text("
                Hi {$user->getFirstName()}!\n
                We are very glad that you are our new member!\n
                Please verify your account by clicking on this button:
                "
            )
            ->html("<a type='button' href='http://localhost:8000/verify_account?{$tokenHandler->tokenItself}&{$tokenHandler->expires}'>Verify</a>");

        $mailer->send($email);
    }
}