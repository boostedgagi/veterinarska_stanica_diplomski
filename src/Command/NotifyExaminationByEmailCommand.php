<?php

namespace App\Command;

use App\Entity\HealthRecord;
use App\Enum\NotifyingTimeRange;
use App\Repository\HealthRecordRepository;
use App\Service\TemplatedEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

#[AsCommand(
    name: 'NotifyExaminationByEmail',
    description: 'E-mail owners about upcoming appointments (--time-range=today|next_week)',
    aliases: ['examination:notify:email']
)]
class NotifyExaminationByEmailCommand extends Command
{
    public function __construct(
        private readonly TemplatedEmailService $emailService,
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

    /**
     * @throws TransportExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $range = NotifyingTimeRange::tryFrom((string) $input->getOption('time-range'));
        if ($range === null) {
            $output->writeln('<error>--time-range must be "today" or "next_week".</error>');

            return Command::INVALID;
        }
        $timeRange = $range->value;
        $scheduledHealthRecords = $this->healthRecRepo->timeRangeHealthRecords($timeRange);

        if (count($scheduledHealthRecords) === 0) {
            return Command::SUCCESS;
        }

        foreach ($scheduledHealthRecords as $healthRecord) {

            $this->setNotifiedByRange($healthRecord,$timeRange);
            $this->emailService->notifyUserAboutAppointment($healthRecord);

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
