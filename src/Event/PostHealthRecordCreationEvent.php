<?php

namespace App\Event;

use App\Entity\HealthRecord;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;

class PostHealthRecordCreationEvent
{
    public const NAME = 'healthrecord.postcreation';

    public HealthRecord $healthRecord;

    public EntityManagerInterface $em;

    public function __construct(
        HealthRecord $healthRecord,
        EntityManagerInterface $em
    )
    {
        $this->healthRecord = $healthRecord;
        $this->em = $em;
    }
}