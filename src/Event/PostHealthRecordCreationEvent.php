<?php

namespace App\Event;

use App\Entity\HealthRecord;
use Doctrine\ORM\EntityManagerInterface;

class PostHealthRecordCreationEvent
{
    public const NAME = 'healthrecord.postcreation';

    public function __construct(public HealthRecord $healthRecord, public EntityManagerInterface $em)
    {
    }
}