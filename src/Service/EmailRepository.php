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
                        token_id={$token->getId()}&
                        token={$token->getToken()}&
                        expires={$token->getExpires()}&
                        user_id={$user->getId()}'
                >
                    Verify
                </a>");

        $mailer->send($email);
    }

    public function sendQRCodeToOwner(User $user, MailerInterface $mailer,Pet $pet): void
    {
        $email = (new Email())
            ->from('yourqrcode@vetshop.com')
            ->to($user->getEmail())
            ->subject('We made qr code just for your pet!')
            ->html("
                <p>here goes image yes..</p>
            ");

        $mailer->send($email);
    }


//    public function generatePetQRCode(Pet $pet): QrCodeResponse
//    {
//        $url = 'http://localhost:8000/found_pet?id='.$pet->getId();
//        $qrCode = new QrCode($url);
//        return new QrCodeResponse($qrCode);
//    }
}