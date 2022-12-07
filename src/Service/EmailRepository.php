<?php

namespace App\Service;

use App\Entity\Pet;
use App\Entity\User;
use App\Entity\VerifyAccount;
use Endroid\QrCode\QrCode;
use Endroid\QrCodeBundle\Response\QrCodeResponse;
use phpDocumentor\Reflection\File;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailRepository
{
    private MailerInterface $mailer;

    public function __construct($mailer)
    {
        $this->mailer = $mailer;
    }

    public function sendWelcomeEmail(User $user, VerifyAccount $token): void
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
                        token_id={$token->getId()}&
                        token={$token->getToken()}&
                        expires={$token->getExpires()}&
                        user_id={$user->getId()}'
                >
                    Verify
                </a>");

        $this->mailer->send($email);
    }

    public function sendQrCodeWithMail(Pet $pet,string $qrCodePath):void
    {
        $email = (new Email())
            ->from('yourqrcode@vetshop.com')
            ->to($pet->getOwner()->getEmail())
            ->subject('We made qr code just for your pet!')
            ->html("
                <h4 style='font-weight: 500;'>This qr code is supposed to be located in your pet's necklace
                    and also to be scanned if your pet is lost and been found after.</h4>
                <img 
                    src='http://5877-79-101-104-174.ngrok.io/".$qrCodePath."' 
                    height='140px' 
                    width='140px' 
                    alt='qr-code'>
            ");

        $this->mailer->send($email);
    }
}