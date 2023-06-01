<?php

namespace App\Command;

use App\Repository\TokenEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'delete-expired-tokens',
    description: 'Delete all expired tokens that are not used neither once.',
    aliases: ['tokens:delete-expired']
)]
class DeleteExpiredTokensCommand extends Command
{
    private EntityManagerInterface $em;
    private TokenEntityRepository $tokenEntityRepo;

    /**
     * @param EntityManagerInterface $em
     * @param TokenEntityRepository $tokenEntityRepo
     */
    public function __construct(EntityManagerInterface $em, TokenEntityRepository $tokenEntityRepo)
    {
        $this->em = $em;
        $this->tokenEntityRepo = $tokenEntityRepo;

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
            'Okay let\'s delete expired tokens!',
            '----------------------------------'
        ]);

        $expiredTokens = $this->tokenEntityRepo->getExpiredTokens();
        if(count($expiredTokens)===0){
            $output->writeln([
                'Great! But...',
                '----------------------------------',
                'All expired tokens are already deleted!'
            ]);

            return Command::SUCCESS;
        }

        foreach ($expiredTokens as $expiredToken){
            try{
                $this->em->remove($expiredToken);
                $this->em->flush();
            }
            catch (Exception $e){
                $output->writeln([
                   'Something bad happened, ',
                   'please try again later...',
                    'error: '.$e
                ]);

                return Command::FAILURE;
            }
        }

        $output->writeln([
            'Great!',
            '~~~',
            'All expired/unused tokens are deleted!',
            'We had '.count($expiredTokens).' of them.'
        ]);

        return Command::SUCCESS;
    }
}
