<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Examination;
use PHPUnit\Framework\TestCase;

class ExaminationTest extends TestCase
{

    public function testCanGetAndSetData(): void
    {
        $examination = new Examination();
        $examination->setName('Pregled usiju');
        $examination->setDuration(45);
        $examination->setPrice(1400);

        self::assertEquals('Pregled usiju', $examination->getName());
        self::assertEquals(45, $examination->getDuration());
        self::assertEquals(1400, $examination->getPrice());
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
