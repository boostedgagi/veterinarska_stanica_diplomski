<?php

namespace App\Command;

use App\Repository\HealthRecordRepository;
use App\Service\TemplatedEmailService;
use App\Service\ExportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

#[AsCommand(
    name: 'MonthlyHealthRecordExport',
    description: 'Export the last 30 days of health records to CSV and e-mail it',
    aliases: ['hr:export:csv']
)]
class MonthlyHealthRecordExportCommand extends Command
{
    public function __construct(
        public readonly HealthRecordRepository $healthRecordRepo,
        public readonly ExportService          $exportService,
        public readonly TemplatedEmailService $emailService
    )
    {
        parent::__construct();
    }

    /**
     * @throws TransportExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $lastMonthHealthRecords = $this->healthRecordRepo->getLastMonthHealthRecords();

        $fileName = sprintf('%s_%s_health_records.csv', date('n'), date("Y", time()));

        $filePath = $this->exportService->exportHealthRecords($lastMonthHealthRecords, $fileName);
        $this->emailService->sendMonthlyCSVByMail($filePath);

        return Command::SUCCESS;
    }
}
