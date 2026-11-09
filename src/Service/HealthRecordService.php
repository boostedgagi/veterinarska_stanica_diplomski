<?php

namespace App\Service;

use App\Entity\HealthRecord;
use App\Entity\User;
use App\Enum\HealthRecordStatus;
use App\Model\CancelHealthRecord;
use DateTime;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class HealthRecordService
{
    public function __construct(private readonly TemplatedEmailService $emailService)
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

            $this->emailService->sendCancelMailByVet(
                $healthRecord->getPet(),
                $cancel->getCancelMessage()
            );
        }
        $healthRecord->setStatus(HealthRecordStatus::CANCELED->value);

        return CancelHealthRecord::getSuccessfullCancelMessage();
    }
}