<?php

namespace App\Service;

use App\Entity\HealthRecord;
use App\Entity\User;
use App\Enum\HealthRecordStatus;
use App\Model\CancelHealthRecord;
use DateTime;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

class HealthRecordService
{
    public function __construct(private readonly MailerInterface $mailer)
    {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function cancel(HealthRecord $healthRecord, CancelHealthRecord $cancel,User $canceler): ?string
    {
        $timeDiff = $healthRecord->getStartedAt()->diff(new DateTime());

        if ($timeDiff->h === 0 && $canceler->getTypeOfUser() === User::TYPE_USER)
        {
            return CancelHealthRecord::getDenyCancelMessage();
        }
        if ($canceler->getTypeOfUser() === User::TYPE_VET)
        {
            $email = new TemplatedEmailService($this->mailer);

            //this should be enqueued
            $email->sendCancelMailByVet(
                $healthRecord->getPet(),
                $cancel->getCancelMessage()
            );
        }
        $healthRecord->setStatus(HealthRecordStatus::CANCELED->value);

        return CancelHealthRecord::getSuccessfullCancelMessage();
    }
}