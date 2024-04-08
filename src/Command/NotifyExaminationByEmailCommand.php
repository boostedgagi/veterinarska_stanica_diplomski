<?php

namespace App\Command;

use App\Entity\HealthRecord;
use App\Enum\NotifyingTimeRange;
use App\Repository\HealthRecordRepository;
use App\Service\TemplatedEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Notifier\NotifierInterface;

#[AsCommand(
    name: 'NotifyExaminationByEmail',
    description: 'Add a short description for your command',
    aliases: ['examination:notify:email']
)]
class NotifyExaminationByEmailCommand extends Command
{
    public function __construct(
        private readonly MailerInterface        $mailer,
        private readonly NotifierInterface      $notifier,
        private readonly HealthRecordRepository $healthRecRepo,
        private readonly EntityManagerInterface $em
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('time-range', null, InputOption::VALUE_REQUIRED, 'Time range');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $timeRange = $input->getOption('time-range');
        $scheduledHealthRecords = $this->healthRecRepo->timeRangeHealthRecords($timeRange);

        if (count($scheduledHealthRecords) === 0) {
            return Command::SUCCESS;
        }

        foreach ($scheduledHealthRecords as $healthRecord) {
            $email = new TemplatedEmailService($this->mailer);

            $this->setNotifiedByRange($healthRecord,$timeRange);
            $email->notifyUserAboutAppointment($this->notifier, $healthRecord);

            $this->em->flush();
        }
        return Command::SUCCESS;
    }

    private function setNotifiedByRange(HealthRecord $healthRecord, string $timeRange): void
    {
        if ($timeRange === NotifyingTimeRange::NEXT_DAY->value) {
            $healthRecord->setNotifiedDayBefore(true);
        }
        else if ($timeRange === NotifyingTimeRange::NEXT_WEEK->value) {
            $healthRecord->setNotifiedWeekBefore(true);
        }
    }
}
