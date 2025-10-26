<?php

namespace App\Controller;

use App\ContextGroup;
use App\Entity\HealthRecord;
use App\Entity\Pet;
use App\Entity\User;
use App\Event\PostHealthRecordCreationEvent;
use App\EventSubscriber\PostHealthRecordCreationSubscriber;
use App\Factory\HealthRecordFactory;
use App\Form\CancelHealthRecordType;
use App\Form\HealthRecordType;
use App\Model\CancelHealthRecord;
use App\Model\PaginatedResult;
use App\Model\PaginationQueryParams;
use App\Repository\HealthRecordRepository;
use App\Service\HealthRecordService;
use App\Service\PaginationService;
use App\Service\TemplatedEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Exception;
use Knp\Component\Pager\PaginatorInterface;
use Nebkam\SymfonyTraits\FormTrait;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class HealthRecordController extends AbstractController
{
    use FormTrait;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TokenStorageInterface  $tokenStorage,
        private readonly EventDispatcherInterface $eventDispatcher
    )
    {
    }

    /**
     * @throws Exception
     * @throws TransportExceptionInterface
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
    /*
     * todo refactor creation to factory
     */
    #[Route('/health_record', methods: 'POST')]
    public function create(Request $request, MailerInterface $mailer): Response
    {
        $env = $this->getParameter('kernel.environment');
        $healthRecord = new HealthRecord();
        $email = new TemplatedEmailService($mailer);

        $this->handleJSONForm($request, $healthRecord, HealthRecordType::class);

        $healthRecordFactory = new HealthRecordFactory($this->em);
        $healthRecordFactory->setMadeByVet($healthRecord,$email,$env);

        //these two lines are behaving like a MySQL Trigger but in a Symfony way...
        $event = new PostHealthRecordCreationEvent($healthRecord, $this->em);
        $this->eventDispatcher->dispatch($event, PostHealthRecordCreationEvent::NAME);

        $this->em->flush();

        return $this->json($healthRecord, Response::HTTP_CREATED, [], ['groups' => ContextGroup::CREATE_HEALTH_RECORD]);
    }

    /**
     * @throws TransportExceptionInterface
     */
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
    public function edit(Request $request, ?HealthRecord $healthRecord, MailerInterface $mailer): Response
    {
        if (!$healthRecord) {
            return $this->json(["error" => "Health record not found."]);
        }
        $this->handleJSONForm($request, $healthRecord, HealthRecordType::class);

        if($healthRecord->getCancelMessage()){
            $email = new TemplatedEmailService($mailer);
            $email->sendCancelMailByVet($healthRecord->getPet(),$healthRecord->getCancelMessage());

            $healthRecord->setCancelMessage(null);
        }

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
        path: '/{id}/health_record',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'number')
            ),
            new OA\Parameter(
                name: 'page',
                in: "query",
                required: true,
                schema: new OA\Schema(type: 'number'),
                example: 1
            ),
            new OA\Parameter(
                name: 'limit',
                in: "query",
                required: true,
                schema: new OA\Schema(type: 'number'),
                example: 5
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
    #[Route('/pet/{id}/health_record', requirements: ['id' => Requirements::NUMERIC], methods: 'GET')]
    public function petHealthRecords(Request $request, ?Pet $pet, PaginatorInterface $paginator): Response
    {
        if (!$pet) {
            throw new NotFoundHttpException('Pet not found');
        }

        /**
         * Very important notice is that every collection of data that we want to paginate,
         * needs to be in array format, otherwise just first page of data will be paginated correctly
         */
        $petHealthRecords = $pet->getHealthRecords()->toArray();

        $paginationService = new PaginationService($paginator, $request, $petHealthRecords);
        $paginatedResult = $paginationService->getPaginatedResult();

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
}
