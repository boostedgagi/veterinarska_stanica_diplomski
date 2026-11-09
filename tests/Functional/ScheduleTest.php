<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * The recurring jobs run from the app (#[AsCronTask] on the commands), with one worker:
 * php bin/console messenger:consume scheduler_default
 */
class ScheduleTest extends KernelTestCase
{
    public function testAllJobsAreScheduled(): void
    {
        $tester = new CommandTester(new Application(self::bootKernel())->find('debug:scheduler'));
        $tester->execute([]);
        $output = $tester->getDisplay();

        foreach ([
            ['0 * * * *', 'NotifyExaminationByEmail --time-range=today'],
            ['0 8 * * *', 'NotifyExaminationByEmail --time-range=next_week'],
            ['0 6 1 * *', 'MonthlyHealthRecordExport'],
            ['30 3 * * *', 'delete-expired-tokens'],
        ] as [$cron, $command]) {
            self::assertMatchesRegularExpression('/' . preg_quote($cron, '/') . '\s.*\(' . preg_quote($command, '/') . '\)/', $output, $command);
        }
    }
}
