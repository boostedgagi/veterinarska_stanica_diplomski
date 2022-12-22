<?php

namespace App\Service;

use App\Entity\HealthRecord;
use App\Entity\Pet;
use App\Entity\User;
use App\Entity\VerifyAccount;
use DateTime;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\Recipient;

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
        $ngrok = getenv('NGROK_TUNNEL');

        $email = (new Email())
            ->from('yourqrcode@vetshop.com')
            ->to($pet->getOwner()->getEmail())
            ->subject('We made qr code just for your pet!')
            ->html("
                <h4 style='font-weight: 500;'>This qr code is supposed to be located in your pet's necklace
                    and also to be scanned if your pet is lost and been found after.</h4>
                <img 
                    src=".$ngrok.'/'.$qrCodePath."
                    height='140px' 
                    width='140px' 
                    alt='qr-code'>
            ");

        $this->mailer->send($email);
    }

    public function sendCancelMailByVet(Pet $pet,string $cancelText):void
    {
        $ngrok = getenv('NGROK_TUNNEL');

        $email = (new Email())
            ->from('cancel@vetshop.com')
            ->to($pet->getOwner()->getEmail())
            ->subject('Your pet\'s examination is canceled.')
            ->html("
                <h4 style='font-weight: 500;'>".$cancelText."</h4>
            ");

        $this->mailer->send($email);
    }

    public function notifyUserAboutPetHaircut(NotifierInterface $notifier,HealthRecord $healthRecord):void
    {

        $pet = $healthRecord->getPet();

        $date = $healthRecord->getStartedAt();
        $resultDate = $date->format('Y-m-d H:i:s');
        $examination = $healthRecord->getExamination();
        $notification = (new Notification('Reminder from VetShop',['email']))
            ->content("Hi ".$pet->getOwner()->getFirstName()."!
            
            We are notifying you that your pet named ".$pet->getName().",
             have ".$examination->getName()." in the ".$resultDate.".
            Examination is ".$examination->getDuration()." minutes long.
            See you then!
            
            Your VetShop!");

        $user = $pet->getOwner();

        $recipient = new Recipient(
            $user->getEmail()
        );

        $notifier->send($notification,$recipient);
    }
}