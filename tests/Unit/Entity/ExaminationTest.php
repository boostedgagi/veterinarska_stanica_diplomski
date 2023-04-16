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
            ->setDuration(100)
            ->setPrice(1000);

        self::assertEquals('Pregled',$examination->getName());
    }

    /**
     * @dataProvider examinationDurationProvider
     */
    public function testExaminationDurationWithProvider(int $duration, string $descriptiveLength): void
    {
        $examination = new Examination();
        $examination->setDuration($duration);
        self::assertEquals($descriptiveLength, $examination->descriptiveLength(), 'Examination is not longer than one hour.');
    }

    public function examinationDurationProvider()
    {
        yield '60 or more minutes long examination' => [60, 'Long'];
        yield '30 or more minutes long examination' => [30, 'Medium'];
        yield '15 or more minutes long examination' => [15, 'Short'];
        yield '0 or more minutes long examination' => [0, 'Mini'];
    }
}
