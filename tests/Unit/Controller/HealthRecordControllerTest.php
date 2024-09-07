<?php

namespace App\Tests\Unit\Controller;

use App\Entity\Examination;
use App\Entity\HealthRecord;
use App\Tests\Unit\BaseTestCase;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HealthRecordControllerTest extends BaseTestCase
{
    public function testMakeHealthRecord(): void
    {
        $healthRecord = (new HealthRecord())
            ->setMadeByVet(false)
            ->setStatus('waiting')
            ->setNotifiedDayBefore(false)
            ->setNotifiedWeekBefore(false)
            ->setStartedAt(new DateTime('2024-03-25 20:07:56'))
            ->setFinishedAt(new DateTime('2024-03-25 22:07:56'))
            ->setAtPresent(false)
            ->setExamination(null)
            ->setVet(null)
            ->setComment('good');

        $this->em->persist($healthRecord);
        $this->em->flush();
    }
}
