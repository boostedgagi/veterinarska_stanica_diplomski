<?php

namespace App\Command;

use App\Entity\HealthRecord;
use App\Repository\HealthRecordRepository;
use App\Service\TemplatedEmail;
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
        private readonly MailerInterface $mailer,
        private readonly NotifierInterface $notifier,
        private readonly HealthRecordRepository $healthRecRepo,
        private readonly EntityManagerInterface $em
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('time-range', null, InputOption::VALUE_REQUIRED, 'Time range')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $timeRange = $input->getOption('time-range');

        $healthRecordsToNotify = $this->healthRecRepo->getExaminationsInTimeRange($timeRange);
//        dd(count($healthRecordsToNotify));
        if(count($healthRecordsToNotify)===0)
        {
            return Command::SUCCESS;
        }
        /** @var HealthRecord $healthRecord */
        foreach ($healthRecordsToNotify as $healthRecord) {
            try {
                $email = new TemplatedEmail($this->mailer);

                $email->notifyUserAboutPetHaircut($this->notifier, $healthRecord);

                $healthRecord->setNotifiedWeekBefore(true);

                $this->em->persist($healthRecord);
                $this->em->flush();
            } catch (Exception $exception)
            {
                return Command::FAILURE;
            }
        }
        return Command::SUCCESS;
    }
}
