<?php

namespace App\Service;

use App\ApiClient;
use App\Entity\HealthRecord;
use App\Entity\Pet;
use App\Entity\User;
use App\Entity\Token;
use Endroid\QrCode\Writer\Result\ResultInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File;

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
        $apiUrl = ApiClient::$websiteUrl;

        $email = (new TemplatedEmail())
            ->to($user->getEmail())
            ->subject('Welcome to the vetShop.')
            ->htmlTemplate('email/welcome.html.twig')
            ->context([
                'user'=>$user,
                'token'=>$token
            ]);

        $this->mailer->send($email);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendMailToNewVet(User $vet, string $password): void
    {
        $email = (new TemplatedEmail())
//            ->from('boostedgagi@boostedgagi.com')
            ->to($vet->getEmail())
            ->subject('Welcome to the vetShop')
            ->htmlTemplate('email/welcomeNewVet.html.twig')
            ->context([
                'vet'=>$vet,
                'password'=>$password
            ]);

        $this->mailer->send($email);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendQrCodeWithMail(Pet $pet, ResultInterface $qrCodePath): void
    {
        $email = (new TemplatedEmail())
            ->to($pet->getOwner()->getEmail())
            ->subject('Your pet\'s QR code.')
            ->htmlTemplate('email/qrCode.html.twig')
            ->context([
                'pet'=>$pet,
                'QRCode' => $qrCodePath,
                'host' => ApiClient::$websiteUrl
            ]);

        $this->mailer->send($email);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendCancelMailByVet(Pet $pet, string $cancelText): void
    {
        $email = (new TemplatedEmail())
//            ->from('cancel@vetshop.com')
            ->to($pet->getOwner()->getEmail())
            ->subject('Examination of your pet is canceled.')
            ->htmlTemplate('email/cancelAppointment.html.twig');

        $this->mailer->send($email);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function notifyUserAboutAppointment(HealthRecord $healthRecord): void
    {
        $pet = $healthRecord->getPet();

        $email = (new TemplatedEmail())
            ->to($pet->getOwner()->getEmail())
            ->subject('Appointment notification')
            ->htmlTemplate('email/scheduledAppointment.html.twig')
            ->context(
                ['healthRecord' => $healthRecord]);

        $this->mailer->send($email);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function notifyVetAboutAppointment(HealthRecord $healthRecord): void
    {
        $pet = $healthRecord->getPet();

        $email = (new TemplatedEmail())
            ->to($pet->getOwner()->getVet()->getEmail())
            ->subject('Appointment notification')
            ->htmlTemplate('email/scheduledAppointmentForVet.html.twig')
            ->context(
                ['healthRecord' => $healthRecord]);

        $this->mailer->send($email);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendPasswordRequest(Token $token, string $emailAddress): void
    {
        $email = (new TemplatedEmail())
            ->to($emailAddress)
            ->subject('Your password renewal request.')
            ->htmlTemplate('email/passwordRenewalRequest.html.twig')
            ->context([
                'token' => $token,
                'emailAddress' => $emailAddress
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