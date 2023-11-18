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
    name: 'DailyNotifyExaminationsCommand',
    description: 'Add a short description for your command',
    aliases: ['notify:examinations:day']
)]
class DailyNotifyExaminationsCommand extends Command
{
    private MailerInterface $mailer;
    private NotifierInterface $notifier;
    private HealthRecordRepository $healthRecRepo;
    private EntityManagerInterface $em;

    /**
     * @param MailerInterface $mailer
     * @param NotifierInterface $notifier
     * @param HealthRecordRepository $healthRecRepo
     * @param EntityManagerInterface $em
     */
    public function __construct(MailerInterface $mailer, NotifierInterface $notifier, HealthRecordRepository $healthRecRepo, EntityManagerInterface $em)
    {
        $this->mailer = $mailer;
        $this->notifier = $notifier;
        $this->healthRecRepo = $healthRecRepo;
        $this->em = $em;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln([
            'Okay let\'s notify pet owners!',
            '~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~'
        ]);

        $examinationsToRemind = $this->healthRecRepo->getExaminationsInTimeRange('next week');

        if(count($examinationsToRemind)===0){
            $output->writeln([
                'Great! But...',
                '~~~',
                'All users are already notified!'
            ]);

            return Command::SUCCESS;
        }
        /** @var HealthRecord $examination */
        foreach ($examinationsToRemind as $examination) {
            try {
                $email = new TemplatedEmail($this->mailer);

                $email->notifyUserAboutPetHaircut($this->notifier, $examination);

                $examination->setNotifiedDayBefore(true);

                $this->em->persist($examination);
                $this->em->flush();
            }
            catch (Exception $exception) {
                $output->writeln([
                    'Something bad happened:',
                    'error: '.$exception
                ]);

                return Command::FAILURE;
            }
        }

        $output->writeln([
            'Great!',
            '~~~',
            'Users are notified!'
        ]);

        return Command::SUCCESS;
    }
}
