<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\VerifyAccount;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailRepository
{
    public function sendWelcomeEmail(User $user, MailerInterface $mailer, VerifyAccount $token): void
    {
        $email = (new Email())
            ->from('welcome@vetshop.com')
            ->to($user->getEmail())
            ->subject('Welcome to the vetShop')
            ->html("
                <p>
                    Hi {$user->getFirstName()}!<br>
                    We are very glad that you are our new member!<br>
                    Please verify your account by clicking on this button:
                </p>
                <a 
                    type='button' 
                    href='http://localhost:8000/verify_account?
                        token={$token->getToken()}&
                        expires={$token->getExpires()}&
                        id={$user->getId()}'
                >
                    Verify
                </a>");

        $mailer->send($email);
    }
}