<?php

namespace App\Command;

use App\Repository\HealthRecordRepository;
use App\Service\EmailRepository;
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
    aliases: ['notify:examinations']
)]
class NotifyExaminationsCommand extends Command
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

    protected function configure(): void
    {
//        $this
//            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
//            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
//        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln([
            'Okay let\'s notify pet owners!',
            '~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~'
        ]);

        $examinationsToRemind = $this->healthRecRepo->getExaminationsInNextSevenDays();

        if(count($examinationsToRemind)===0){
            $output->writeln([
                'Great! But...',
                '~~~',
                'All users are already notified!'
            ]);

            return Command::SUCCESS;
        }
        foreach ($examinationsToRemind as $examination) {
            try {
                $email = new EmailRepository($this->mailer);
                //$examination is the HealthRecordType
                $email->notifyUserAboutPetHaircut($this->notifier, $examination);

                $examination->setNotified(true);

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
