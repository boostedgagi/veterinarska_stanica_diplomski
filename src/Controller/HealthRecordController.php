<?php

namespace App\Controller;

use App\Entity\HealthRecord;
use App\Entity\Pet;
use App\Entity\User;
use App\Form\HealthRecordType;
use App\Repository\HealthRecordRepository;
use App\Repository\PetRepository;
use App\Repository\UserRepository;
use App\Service\EmailRepository;
use App\Service\JwtService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Nebkam\SymfonyTraits\FormTrait;
use phpDocumentor\Reflection\Types\This;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Date;

class HealthRecordController extends AbstractController
{
    use FormTrait;

    private EntityManagerInterface $em;

    public const ONE_MINUTE_IN_SECONDS = 60;
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
    #[Route('/health_record', methods: 'POST')]
    public function create(Request $request, JwtService $jwtService): Response
    {
        $healthRecord = new HealthRecord();

        $decoded = json_decode($request->getContent(), false);
        $this->handleJSONForm($request, $healthRecord, HealthRecordType::class);
        if(!$healthRecord->getVet()){
            return $this->json("You didn't choose any vet.");
        }
        $madeByVet = $this->isVet($jwtService);

        if ($madeByVet && $decoded->atPresent) {
            $this->makeHealthRecordNow($healthRecord);
        }
        else {
            $healthRecord->setMadeByVet(false);
        }
        $this->em->persist($healthRecord);
        $this->em->flush();

        return $this->json($healthRecord, Response::HTTP_CREATED, [], ['groups' => 'healthRecord_created']);
    }

    private function isVet(JwtService $jwtService): bool
    {
        return $jwtService->getCurrentUser()->getTypeOfUser() === 2;
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

    #[Route('/health_record/{id}', methods: 'PUT')]
    public function edit(Request $request, int $id, HealthRecordRepository $repo): Response
    {
//        $form = new HealthRecordType();
//        $form->buildForm();

        $healthRecord = $repo->find($id);

        $this->handleJSONForm($request, $healthRecord, HealthRecordType::class);

        $this->em->persist($healthRecord);
        $this->em->flush();

        return $this->json($healthRecord, Response::HTTP_CREATED, [], ['groups' => 'healthRecord_created']);
    }

    #[Route('/health_record/{id}', methods: 'DELETE')]
    public function delete(Request $request, int $id, HealthRecordRepository $repo): Response
    {
        $healthRecord = $repo->find($id);

        $this->em->remove($healthRecord);
        $this->em->flush();

        return $this->json("", Response::HTTP_NO_CONTENT);
    }

    #[Route('/pets/{id}/health_record',requirements: ['id'=>Requirements::NUMERIC], methods: 'GET')]
    public function getHealthRecordsForOnePet(Pet $pet): Response
    {
        $petHealthRecords = $pet->getHealthRecords();

        return $this->json($petHealthRecords, Response::HTTP_OK, [], ['groups' => 'healthRecord_showAll']);
    }

    #[Route('/health_record/{id}/cancel', methods: 'POST')]
    public function cancel(Request $request, HealthRecordRepository $healthRepo, UserRepository $userRepo, MailerInterface $mailer, int $id): Response
    {
        $healthRecord = $healthRepo->find($id);

        $data = json_decode($request->getContent(), false);

        $cancelText = $data->cancelText;
        $personWhoCancel = $userRepo->find($data->cancelerId);

        $now = new DateTime();
        $timeDiff = $healthRecord->getStartedAt()->diff($now);

        if ($timeDiff->h == 0) {
            return $this->json(['error' => 'Examination is impossible to cancel less than hour before of its start'], Response::HTTP_OK);
        }
        if ($personWhoCancel->getTypeOfUser() === 2) {
            $email = new EmailRepository($mailer);
            $email->sendCancelMailByVet($healthRecord->getPet(), $cancelText);
        }
        $healthRecord->setStatus('canceled');
        $this->em->persist($healthRecord);
        $this->em->flush();

        return $this->json(['status' => 'successfully canceled'], Response::HTTP_OK);
    }


}
