<?php

namespace App\Command;

use App\Entity\HealthRecord;
use App\Repository\HealthRecordRepository;
use App\Service\ExportService;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'MonthlyHealthRecordExport',
    description: 'Add a short description for your command',
    aliases: ['hr:export:csv']
)]
class MonthlyHealthRecordExportCommand extends Command
{
    private HealthRecordRepository $healthRecordRepo;
    private ExportService $exportService;

    public function __construct(HealthRecordRepository $healthRecordRepo, ExportService $exportService)
    {
        $this->healthRecordRepo = $healthRecordRepo;
        $this->exportService = $exportService;

        parent::__construct();
    }

    protected function configure(): void
    {
//        $this
//            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
//            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
//        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Okay let\'s export all health records scheduled in last month.');
        $output->writeln('- - - - -');
        $numericalLastMonth = number_format(date('m', strtotime('-1 month', strtotime(date('Y-m-01')))));

        $lastMonthHealthRecords = $this->healthRecordRepo->getLastMonthHealthRecords(4);

        if (count($lastMonthHealthRecords) === 0) {
            $output->writeln('There is no any scheduled health records in last month.');
        }
        $fileName = sprintf('%s_%s_health_records.csv', $numericalLastMonth, date("Y", time()));

        try {
            $filePath = $this->exportService->exportHealthRecords($lastMonthHealthRecords, $fileName);
            $output->writeln('Data successfully exported in ' . $filePath);
        } catch (Exception $e) {
            $output->writeln('Error occurred: ' . $e->getMessage());
        }
        return Command::SUCCESS;
    }
}
