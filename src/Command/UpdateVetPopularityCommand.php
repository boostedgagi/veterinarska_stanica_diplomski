<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\HealthRecordRepository;
use App\Repository\UserRepository;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'UpdateVetPopularity',
    description: 'Updates vet popularity every once specified by someone.',
    aliases: ['vet:popularity:update'],
)]
class UpdateVetPopularityCommand extends Command
{
    public function __construct(
        private readonly HealthRecordRepository $healthRecordRepo,
        private readonly UserRepository         $userRepo,
        private readonly EntityManagerInterface $em
    )
    {
        parent::__construct();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $vets = $this->userRepo->getAllVets();
        //should separate this and retrieve only ids

        $allHealthRecordCount = $this->healthRecordRepo->allHealthRecordCount();

        /**
         * @var $vet User
         */
        foreach ($vets as $vet)
        {
            $popularity = UserService::calculateVetPopularity($vet, $allHealthRecordCount);
            $vet->setPopularity($popularity);
        }
        $this->em->flush();

        return Command::SUCCESS;
    }
}
