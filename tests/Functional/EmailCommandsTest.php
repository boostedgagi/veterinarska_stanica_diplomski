<?php

namespace App\Tests\Functional;

use App\Entity\HealthRecord;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Mime\Email;

/** The two scheduled commands: appointment reminders and the monthly CSV export. */
class EmailCommandsTest extends ApiTestCase
{
    /** @param array<string, mixed> $input */
    private function console(string $command, array $input = []): int
    {
        $tester = new CommandTester(new Application(self::$kernel)->find($command));

        return $tester->execute($input);
    }

    public function testRemindersAreSentOncePerAppointment(): void
    {
        $owner = $this->owner();
        $soon = $this->appointment($this->vet(), $this->pet($owner), '+2 hours', '+3 hours');
        $this->appointment($this->vet(), $this->pet($this->owner()), '+3 weeks', '+3 weeks 1 hour'); // not in range

        self::assertSame(0, $this->console('examination:notify:email', ['--time-range' => 'today']));
        self::assertEmailCount(1);
        self::assertEmailAddressContains(self::getMailerMessage(), 'To', $owner->getEmail());
        self::assertTrue($this->reload(HealthRecord::class, $soon->getId())->getNotifiedDayBefore());

        self::assertSame(0, $this->console('examination:notify:email', ['--time-range' => 'today']));
        self::assertEmailCount(1, message: 'no second reminder');
    }

    public function testReminderCommandRejectsAnUnknownRange(): void
    {
        self::assertSame(2, $this->console('examination:notify:email', ['--time-range' => 'tomorrow']));
        self::assertEmailCount(0);
    }

    public function testMonthlyExportMailsTheCsvToTheAdmin(): void
    {
        $pet = $this->pet($this->owner());
        $this->appointment($this->vet(), $pet, '-3 days', '-3 days +1 hour');

        self::assertSame(0, $this->console('MonthlyHealthRecordExport'));

        self::assertEmailCount(1);
        /** @var Email $mail */
        $mail = self::getMailerMessage();
        self::assertCount(1, $mail->getAttachments());
        self::assertStringContainsString($pet->getName(), $mail->getAttachments()[0]->getBody());

        $exportDir = self::getContainer()->getParameter('kernel.project_dir') . '/var/exports';
        self::assertNotEmpty(glob($exportDir . '/*.csv'), 'written outside public/');
    }
}
