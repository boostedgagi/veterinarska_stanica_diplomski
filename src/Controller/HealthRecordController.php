<?php

namespace App\Controller;

use App\Entity\HealthRecord;
use App\Entity\Pet;
use App\Entity\User;
use App\Form\CancelHealthRecordType;
use App\Form\HealthRecordType;
use App\Model\CancelHealthRecord;
use App\Repository\HealthRecordRepository;
use App\Repository\PetRepository;
use App\Repository\UserRepository;
use App\Service\EmailRepository;
use App\Service\JwtService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Nebkam\SymfonyTraits\FormTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class HealthRecordController extends AbstractController
{
    public const ONE_MINUTE_IN_SECONDS = 60;


    use FormTrait;

    private EntityManagerInterface $em;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * @throws Exception
     */
    #[Route('/health_records', methods: 'POST')]
    public function create(Request $request,TokenStorageInterface $tokenStorage): Response
    {
        $healthRecord = new HealthRecord();

        $this->handleJSONForm($request, $healthRecord, HealthRecordType::class);
        if(!$healthRecord->checkHolyTrinity())
            {
            return $this->json('Invalid appointment.');
            }
        $madeByVet = $this->isVet($tokenStorage);

        if ($madeByVet && $healthRecord->getAtPresent()) {
            $this->makeHealthRecordNow($healthRecord);
        }
        else {
            $healthRecord->setMadeByVet(false);
        }
        $this->em->persist($healthRecord);
        $this->em->flush();

        return $this->json($healthRecord, Response::HTTP_CREATED, [], ['groups' => 'healthRecord_created']);
    }

    private function isVet(TokenStorageInterface $tokenStorage): bool
    {
        return JwtService::getCurrentUser($tokenStorage)->getTypeOfUser() === 2;
    }

    /**
     * @throws Exception
     */
    private function makeHealthRecordNow(HealthRecord $healthRecord):HealthRecord
    {
        $healthRecord->setMadeByVet(true);
        $healthRecord->setStartedAt(new DateTime());

        $examDurationInSeconds = $healthRecord->getExamination()->getDuration() * self::ONE_MINUTE_IN_SECONDS;

        $healthRecord->setFinishedAt(new DateTime('+'.$examDurationInSeconds.'seconds'));

        return $healthRecord;
    }

    #[Route('/health_records/{id}', methods: 'PUT')]
    public function edit(Request $request,?HealthRecord $healthRecord, HealthRecordRepository $repo): Response
    {
        if(!$healthRecord){
            return $this->json(["error"=>"Health record not found."]);
        }
        $this->handleJSONForm($request, $healthRecord, HealthRecordType::class);

        $this->em->persist($healthRecord);
        $this->em->flush();

        return $this->json($healthRecord, Response::HTTP_CREATED, [], ['groups' => 'healthRecord_created']);
    }

    #[Route('/health_records/{id}', methods: 'GET')]
    public function showOne(?HealthRecord $healthRecord): Response
    {
        if(!$healthRecord){
            return $this->json(["error"=>"Health record not found."]);
        }

        return $this->json($healthRecord, Response::HTTP_OK, [], ['groups' => 'healthRecord_showAll']);
    }

    #[Route('/health_records/{id}', methods: 'DELETE')]
    public function delete(?HealthRecord $healthRecord): Response
    {
        if(!$healthRecord){
            return $this->json(["error"=>"Health record not found."]);
        }
        $this->em->remove($healthRecord);
        $this->em->flush();

        return $this->json("", Response::HTTP_NO_CONTENT);
    }

    #[Route('/pets/{id}/health_records',requirements: ['id'=>Requirements::NUMERIC], methods: 'GET')]
    public function getHealthRecords(?Pet $pet): Response
    {
        if(!$pet){
            return $this->json(["error"=>"Pet not found."]);
        }
        $petHealthRecords = $pet->getHealthRecords();

        return $this->json($petHealthRecords, Response::HTTP_OK, [], ['groups' => 'healthRecord_showAll']);
    }

    #[Route('/users/{id}/health_records',requirements: ['id'=>Requirements::NUMERIC], methods: 'GET')]
    public function getAllUserHealthRecords(?User $user,HealthRecordRepository $healthRecordRepo): Response
    {
        if(!$user){
            return $this->json(["error"=>"Health record not found."]);
        }
        $allHealthRecords = $healthRecordRepo->findAllHealthRecords($user);
        
        return $this->json($allHealthRecords, Response::HTTP_OK, [], ['groups' => 'healthRecord_showAll']);
    }

    #[Route('/health_records/{id}/cancel', methods: 'POST')]
    public function cancel(Request $request, ?HealthRecord $healthRecord, UserRepository $userRepo, MailerInterface $mailer): Response
    {
        if(!$healthRecord)
            {
            return $this->json("Health record not found.");
            }

        $cancel = new CancelHealthRecord();
        $this->handleJSONForm($request,$cancel,CancelHealthRecordType::class);

        $canceler = $userRepo->find($cancel->getCanceler());
        $now = new DateTime();
        $timeDiff = $healthRecord->getStartedAt()->diff($now);

        if ($timeDiff->h == 0 && $canceler->getTypeOfUser()===User::TYPE_USER)
            {
            return $this->json(['error' => $cancel::getDenyCancelMessage()]);
            }
        if ($canceler->getTypeOfUser() === 2)
            {
            $email = new EmailRepository($mailer);
            $email->sendCancelMailByVet(
                $healthRecord->getPet(),
                $cancel->getCancelContent());
            }
        $healthRecord->setStatus($healthRecord::STATUS_CANCELED);

        $this->em->persist($healthRecord);
        $this->em->flush();

        return $this->json(['status' => 'Examination successfully canceled.'], Response::HTTP_OK);
    }
}
