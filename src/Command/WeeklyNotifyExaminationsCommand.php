<?php

namespace App\Command;

use App\Entity\HealthRecord;
use App\Repository\HealthRecordRepository;
use App\Service\TemplatedEmail;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PhpParser\Node\Stmt\Return_;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Notifier\NotifierInterface;

#[AsCommand(
    name: 'notify-examinations',
    description: 'Notify all pet owners whose pets have an scheduled examination in the next 7 days',
    aliases: ['notify:examinations:week']
)]
class WeeklyNotifyExaminationsCommand extends Command
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly  NotifierInterface $notifier,
        private readonly HealthRecordRepository $healthRecRepo,
        private readonly EntityManagerInterface $em
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
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

                $examination->setNotifiedWeekBefore(true);

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
