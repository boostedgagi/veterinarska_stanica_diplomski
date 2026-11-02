<?php

namespace App\Event;

use App\Entity\HealthRecord;
use Doctrine\ORM\EntityManagerInterface;

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