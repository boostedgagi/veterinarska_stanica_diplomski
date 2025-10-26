<?php

namespace App\Factory;

use App\Entity\HealthRecord;
use App\Repository\HealthRecordRepository;
use App\Service\TemplatedEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class HealthRecordFactory
{
    public function __construct(
        public readonly EntityManagerInterface $em
    ){
    }

    /**
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function setMadeByVet(HealthRecord $healthRecord, TemplatedEmailService $email, $env): void
    {
        if(!$healthRecord->isMadeByVet() && !$healthRecord->getAtPresent()){
            $healthRecord->setMadeByVet(false);
            if ($env !== 'test') {
                $email->notifyUserAboutAppointment($healthRecord);
            }
        }
        else{
            $healthRecord->makeHealthRecordNow();
        }
        $this->em->persist($healthRecord);
    }

}
