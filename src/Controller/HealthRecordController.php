<?php

namespace App\Controller;

use App\ContextGroup;
use App\Entity\HealthRecord;
use App\Entity\Pet;
use App\Entity\User;
use App\Form\CancelHealthRecordType;
use App\Form\HealthRecordType;
use App\Model\CancelHealthRecord;
use App\Model\PaginatedResult;
use App\Repository\HealthRecordRepository;
use App\Service\HealthRecordService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Exception;
use Knp\Component\Pager\PaginatorInterface;
use Nebkam\SymfonyTraits\FormTrait;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

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
            ),
            new OA\Response(
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: 'Unprocessable, date format possibly could be in the bad format.'
            ),
        ]
    )]
    #[Route('/health_record', methods: 'POST')]
    public function create(Request $request): Response
    {
        $healthRecord = new HealthRecord();

        $this->handleJSONForm($request, $healthRecord, HealthRecordType::class);

        if ($healthRecord->isMadeByVet()===true && $healthRecord->getAtPresent()===true)
        {
            $healthRecord->makeHealthRecordNow();
        }
        //this else could be disposed
        else {
            $healthRecord->setMadeByVet(false);
        }

        $this->em->persist($healthRecord);
        $this->em->flush();

        return $this->json($healthRecord, Response::HTTP_CREATED, [], ['groups' => ContextGroup::CREATE_HEALTH_RECORD]);
    }

    #[OA\Put(
        path: '/health_record/{id}',
        description: 'Change examination data.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: new Model(type: HealthRecordType::class)
            )
        ),
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'number')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Returns updated examination.',
                content: new Model(
                    type: HealthRecord::class,
                    groups: [ContextGroup::CREATE_HEALTH_RECORD]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Health record with specified ID not found.'
            )
        ]
    )]
    #[Route('/health_record/{id}', methods: 'PUT')]
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

    #[OA\Get(
        path: '/health_record/{id}',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'number')
            )
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'One health record data.',
                content: new Model(
                    type: HealthRecord::class,
                    groups: [ContextGroup::SHOW_HEALTH_RECORD]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Health record with specified ID not found.',
            )
        ]
    )]
    #[Route('/health_record/{id}', methods: 'GET')]
    public function showOne(?HealthRecord $healthRecord): Response
    {
        if (!$healthRecord) {
            return $this->json(["error" => "Health record not found."]);
        }

        return $this->json($healthRecord, Response::HTTP_OK, [], ['groups' => ContextGroup::SHOW_HEALTH_RECORD]);
    }

    #[OA\Delete(
        path: '/health_record/{id}',
        description: 'Delete one health record.',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'number')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: 'Returns empty response.'
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Examination with specified ID not found.'
            )
        ]
    )]
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

    #[OA\Get(
        path: '/pet/{id}/health_records',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'number')
            )
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'All health records for one pet.',
                content: new Model(
                    type: HealthRecord::class,
                    groups: [ContextGroup::SHOW_HEALTH_RECORD]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Health record for specified pet not found.',
            )
        ]
    )]
    #[Route('/pet/{id}/health_records', requirements: ['id' => Requirements::NUMERIC], methods: 'GET')]
    public function petHealthRecords(Request $request,?Pet $pet,PaginatorInterface $paginator): Response
    {
        if (!$pet) {
            return $this->json(["error" => "Pet not found."]);
        }
        $petHealthRecords = $pet->getHealthRecords();

        $pagination = $paginator->paginate(
            $petHealthRecords,
            $request->query->getInt('page'),
            $request->query->getInt('limit')
        );

        $paginatedResult = new PaginatedResult(
            $pagination->getItems(),
            $pagination->getCurrentPageNumber(),
            $pagination->getTotalItemCount()
        );

        return $this->json($paginatedResult, Response::HTTP_OK, [], ['groups' => ContextGroup::SHOW_HEALTH_RECORD]);
    }

    #[Route('/user/{id}/health_records', requirements: ['id' => Requirements::NUMERIC], methods: 'GET')]
    public function ownerHealthRecords(?User $user, HealthRecordRepository $healthRecordRepo): Response
    {
        if (!$user) {
            return $this->json(["error" => "Health record not found."]);
        }
        $allHealthRecords = $healthRecordRepo->findAllHealthRecordsForUser($user);

        return $this->json($allHealthRecords, Response::HTTP_OK, [], ['groups' => ContextGroup::SHOW_HEALTH_RECORD]);
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[OA\Post(
        requestBody: new OA\RequestBody(
            description: 'Cancel health record here..',
            required: true,
            content: new OA\JsonContent(
                ref: new Model(type: CancelHealthRecordType::class)
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Returns message.'
            ),
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: 'Error'
            ),
            new OA\Response(
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: 'Unprocessable, date format possibly wrong.'
            ),
        ]
    )]
    #[Route('/health_record/{id}/cancel', methods: 'POST')]
    public function cancel(Request $request, ?HealthRecord $healthRecord,HealthRecordService $healthRecordService): Response
    {
        if (!$healthRecord)
        {
            return $this->json("Health record not found.");
        }
        $cancel = new CancelHealthRecord();
        $this->handleJSONForm($request, $cancel, CancelHealthRecordType::class);

        $message = $healthRecordService->cancel($healthRecord,$cancel);

        $this->em->flush();

        return $this->json(['message' => $message], Response::HTTP_OK);
    }
}
