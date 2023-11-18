<?php

namespace App\Controller;

use App\ContextGroup;
use App\Entity\HealthRecord;
use App\Entity\Pet;
use App\Entity\User;
use App\Form\CancelHealthRecordType;
use App\Form\HealthRecordType;
use App\Model\CancelHealthRecord;
use App\Repository\HealthRecordRepository;
use App\Repository\PetRepository;
use App\Repository\UserRepository;
use App\Service\TemplatedEmail;
use App\Service\UserService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Nebkam\SymfonyTraits\FormTrait;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use OpenApi\Attributes as OA;

class HealthRecordController extends AbstractController
{
    use FormTrait;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TokenStorageInterface $tokenStorage
    )
    {
    }

    /**
     * @throws Exception
     * Insert new health record here.
     */
    #[OA\Post(
        requestBody: new OA\RequestBody(
            description: 'Insert new product data here,measurements are metric.',
            required: true,
            content: new OA\JsonContent(
                ref: new Model(type: HealthRecordType::class)
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Returns created health record.',
                content: new Model(
                    type: HealthRecord::class,
                    groups: [ContextGroup::CREATE_HEALTH_RECORD]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: 'Error'
            )
        ]
    )]
    #[Route('/health_record', methods: 'POST')]
    public function create(Request $request): Response
    {
        $healthRecord = new HealthRecord();

        $this->handleJSONForm($request, $healthRecord, HealthRecordType::class);

        if ($healthRecord->isMadeByVet() && $healthRecord->getAtPresent())
        {
            $healthRecord->makeHealthRecordNow();
        }
        else {
            $healthRecord->setMadeByVet(false);
        }

        $this->em->persist($healthRecord);
        $this->em->flush();

        return $this->json($healthRecord, Response::HTTP_CREATED, [], ['groups' => ContextGroup::CREATE_HEALTH_RECORD]);
    }

    //move this out of this controller

    #[Route('/health_records/{id}', methods: 'PUT')]
    public function edit(Request $request, ?HealthRecord $healthRecord, HealthRecordRepository $repo): Response
    {
        if (!$healthRecord) {
            return $this->json(["error" => "Health record not found."]);
        }
        $this->handleJSONForm($request, $healthRecord, HealthRecordType::class);

        $this->em->persist($healthRecord);
        $this->em->flush();

        return $this->json($healthRecord, Response::HTTP_CREATED, [], ['groups' => ContextGroup::CREATE_HEALTH_RECORD]);
    }

    #[Route('/health_record/{id}', methods: 'GET')]
    public function showOne(?HealthRecord $healthRecord): Response
    {
        if (!$healthRecord) {
            return $this->json(["error" => "Health record not found."]);
        }

        return $this->json($healthRecord, Response::HTTP_OK, [], ['groups' => ContextGroup::SHOW_HEALTH_RECORD]);
    }

    #[Route('/health_record/{id}', methods: 'DELETE')]
    public function delete(?HealthRecord $healthRecord): Response
    {
        if (!$healthRecord) {
            return $this->json(["error" => "Health record not found."]);
        }
        $this->em->remove($healthRecord);
        $this->em->flush();

        return $this->json("", Response::HTTP_NO_CONTENT);
    }

    #[Route('/pet/{id}/health_record', requirements: ['id' => Requirements::NUMERIC], methods: 'GET')]
    public function petHealthRecords(?Pet $pet): Response
    {
        if (!$pet) {
            return $this->json(["error" => "Pet not found."]);
        }
        $petHealthRecords = $pet->getHealthRecords();

        return $this->json($petHealthRecords, Response::HTTP_OK, [], ['groups' => ContextGroup::SHOW_HEALTH_RECORD]);
    }

    #[Route('/users/{id}/health_records', requirements: ['id' => Requirements::NUMERIC], methods: 'GET')]
    public function ownerHealthRecords(?User $user, HealthRecordRepository $healthRecordRepo): Response
    {
        if (!$user) {
            return $this->json(["error" => "Health record not found."]);
        }
        $allHealthRecords = $healthRecordRepo->findAllHealthRecords($user);

        return $this->json($allHealthRecords, Response::HTTP_OK, [], ['groups' => ContextGroup::SHOW_HEALTH_RECORD]);
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/health_record/{id}/cancel', methods: 'POST')]
    public function cancel(Request $request, ?HealthRecord $healthRecord, UserRepository $userRepo, MailerInterface $mailer): Response
    {
        if (!$healthRecord)
        {
            return $this->json("Health record not found.");
        }

        $cancel = new CancelHealthRecord();
        $this->handleJSONForm($request, $cancel, CancelHealthRecordType::class);

        //this should be separated to service
        $timeDiff = $healthRecord->getStartedAt()->diff(new DateTime());

        if (
            $timeDiff->h === 0 &&
            $cancel->getCanceler()->getTypeOfUser() === User::TYPE_USER
        )
        {
            return $this->json(['message' => CancelHealthRecord::getDenyCancelMessage()]);
        }
        if ($cancel->getCanceler()->getTypeOfUser() === User::TYPE_VET)
        {
            $email = new TemplatedEmail($mailer);

            $email->sendCancelMailByVet(
                $healthRecord->getPet(),
                $cancel->getCancelMessage());
        }
        $healthRecord->setStatus($healthRecord::STATUS_CANCELED);

        $this->em->persist($healthRecord);
        $this->em->flush();

        return $this->json(['message' => CancelHealthRecord::getSuccessfullCancelMessage()], Response::HTTP_OK);
    }
}
