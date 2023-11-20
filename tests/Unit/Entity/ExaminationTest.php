<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Examination;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ExaminationTest extends WebTestCase
{
    public function testCreateExaminationWithRequest(): void
    {

        $examination = (new Examination())
            ->setName("Pregled")
            ->setDuration(60)
            ->setPrice(1000);

        self::assertEquals('Pregled',$examination->getName());
    }

}
