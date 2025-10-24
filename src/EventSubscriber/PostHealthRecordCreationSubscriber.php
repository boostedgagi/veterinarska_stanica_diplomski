<?php

namespace App\EventSubscriber;

use App\Entity\HealthRecord;
use App\Entity\User;
use App\Event\PostHealthRecordCreationEvent;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PostHealthRecordCreationSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            PostHealthRecordCreationEvent::NAME => 'onHealthrecordPostcreation',
        ];
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function onHealthrecordPostcreation($event): void
    {
        /**
         * @var $entityManager EntityManagerInterface
         */
        $entityManager = $event->em;

        /**
         * @var $healthRecord HealthRecord
         */
        $healthRecord = $event->healthRecord;

        $healthRecordRepository = $entityManager->getRepository(HealthRecord::class);
        $allHealthRecordCount = $healthRecordRepository->allHealthRecordCount();

        $vet = $healthRecord->getVet();
        $popularity = UserService::calculateVetPopularity($vet, $allHealthRecordCount);
        $vet->setPopularity($popularity);

        /**
         * flush always need to be in controller in order to prevent "Double Flush"
         */
//        $entityManager->flush();
    }
}
