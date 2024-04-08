<?php

namespace App\Service;

use App\ApiClient;
use App\Entity\HealthRecord;
use App\Entity\Pet;
use App\Entity\User;
use App\Entity\Token;
use App\Model\Token as ModelToken;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\Recipient;

class TemplatedEmailService
{
    public function __construct(
        private readonly MailerInterface $mailer
    )
    {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendWelcomeEmail(User $user, Token $token): void
    {
        $apiUrl = ApiClient::$apiUrl;

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
                    href='{$apiUrl}/verify_account?
                        token_id={$token->getId()}&
                        token={$token->getToken()}&
                        expires={$token->getExpires()}&
                        user_id={$user->getId()}'
                >
                    Verify
                </a>");

        $this->mailer->send($email);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendMailToNewVet(User $vet, string $password): void
    {
        $email = (new Email())
            ->from('welcome@vetshop.com')
            ->to($vet->getEmail())
            ->subject('Welcome to the vetShop')
            ->html("
                <p>
                    Hi {$vet->getFirstName()}!<br>
                    Our administrator made you a personal account!<br>
                    It will be your job account where you will be notified about your scheduled examinations, etc.<br>
                    Your email is {$vet->getEmail()}, and password is $password and we suggest to change it after first log in.<br><br>
                    Kind regards,<br>
                    Your VetShop
                </p>
                ");

        $this->mailer->send($email);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendQrCodeWithMail(Pet $pet, string $qrCodePath): void
    {
        $host = ApiClient::$apiUrl;

        $email = (new Email())
            ->from('yourqrcode@vetshop.com')
            ->to($pet->getOwner()->getEmail())
            ->subject('We made qr code just for your pet!')
            ->html("
                <h4 style='font-weight: 500;'>This qr code is supposed to be located in your pet's necklace<br>
                    and also to be scanned if your pet is lost and been found after.</h4>
                <img 
                    src=" . $host . '/' . $qrCodePath . "
                    height='140px' 
                    width='140px' 
                    alt='qr-code'>
            ");

        $this->mailer->send($email);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendCancelMailByVet(Pet $pet, string $cancelText): void
    {
        $email = (new Email())
            ->from('cancel@vetshop.com')
            ->to($pet->getOwner()->getEmail())
            ->subject('Examination of your pet is canceled.')
            ->html("
                <h4 style='font-weight: 500;'>" . $cancelText . "</h4>
            ");

        $this->mailer->send($email);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function notifyUserAboutAppointment(NotifierInterface $notifier, HealthRecord $healthRecord): void
    {
        $pet = $healthRecord->getPet();
        $notification = (new TemplatedEmail())
            ->to($pet->getOwner()->getEmail())
            ->htmlTemplate('email/scheduledAppointment.html.twig')
            ->context(['healthRecord' => $healthRecord]);

        $this->mailer->send($notification);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendPasswordRequest(Token $token,string $emailAddress): void
    {
        $email = (new TemplatedEmail())
            ->to($emailAddress)
            ->subject('Your password renewal request.')
            ->htmlTemplate('email/passwordRenewalRequest.html.twig')
            ->context([
                'token' => $token,
                'emailAddress'=>$emailAddress
            ]);

        $this->mailer->send($email);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendMonthlyCSVByMail(string $CSVPath): void
    {
        $email = (new TemplatedEmail())
            ->to('boostedgagi@boostedgagi.com')
            ->subject('Monthly report of all examinations.')
            ->htmlTemplate('email/monthlyCsvExport.html.twig')
            ->addPart(
                new DataPart(new File($CSVPath))
            );

        $this->mailer->send($email);
    }
}