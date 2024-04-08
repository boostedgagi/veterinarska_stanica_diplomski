<?php

namespace App\Command;

use App\Entity\HealthRecord;
use App\Repository\HealthRecordRepository;
use App\Service\TemplatedEmailService;
use App\Service\ExportService;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;

#[AsCommand(
    name: 'MonthlyHealthRecordExport',
    description: 'Add a short description for your command',
    aliases: ['hr:export:csv']
)]
class MonthlyHealthRecordExportCommand extends Command
{
    public function __construct(
        public readonly HealthRecordRepository $healthRecordRepo,
        public readonly ExportService          $exportService,
        public readonly MailerInterface        $mailer
    )
    {
        parent::__construct();
    }

    /**
     * @throws TransportExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $lastMonthHealthRecords = $this->healthRecordRepo->getLastMonthHealthRecords(7);

        $fileName = sprintf('%s_%s_health_records.csv', date('n'), date("Y", time()));

        $filePath = $this->exportService->exportHealthRecords($lastMonthHealthRecords, $fileName);
        $email = new TemplatedEmailService($this->mailer);
        $email->sendMonthlyCSVByMail($filePath);

        return Command::SUCCESS;
    }
}
