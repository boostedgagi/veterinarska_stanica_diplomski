<?php

namespace App\Controller;

use App\ContextGroup;
use App\Entity\HealthRecord;
use App\Entity\Pet;
use App\Entity\User;
use App\Event\UserRegisterEvent;
use App\EventSubscriber\RegisterEventSubscriber;
use App\Form\LoginType;
use App\Form\UserType;
use App\Model\PaginatedResult;
use App\Model\PaginationQueryParams;
use App\Repository\UserRepository;
use App\Service\LogHandler;
use App\Service\PaginationService;
use App\Service\UploadImage;
use App\Service\UserService;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use MobileDetectBundle\DeviceDetector\MobileDetectorInterface;
use Nebkam\SymfonyTraits\FormTrait;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\TemplatedEmailService;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;


class UserController extends AbstractController
{
    use FormTrait;

    public function __construct(
        private readonly EntityManagerInterface   $em,
        private readonly EventDispatcherInterface $eventDispatcher
    )
    {
    }

    #[OA\Post(
        path: '/user',
        requestBody: new OA\RequestBody(
            description: 'Register',
            required: true,
            content: new OA\JsonContent(
                ref: new Model(type: UserType::class)
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Returns registered user.',
                content: new Model(
                    type: User::class,
                    groups: [ContextGroup::CREATE_USER]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: 'Error'
            )
        ]
    )]
    #[Route('/user', methods: 'POST')]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, MailerInterface $mailer,#[CurrentUser] User $currentUser): Response
    {
        if($currentUser->getTypeOfUser()!==User::TYPE_ADMIN){
            return $this->json('You are not enabled to do this.',Response::HTTP_FORBIDDEN);
        }

        $user = new User();

        $this->handleJSONForm($request, $user, UserType::class);
        if (!$user->getPlainPassword()) {
            return $this->json("Password not valid.");
        }
        if ($plainPassword = $user->getPlainPassword()) {
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $plainPassword
            );
            $user->setPassword($hashedPassword);
        }

        $this->em->persist($user);
        $this->em->flush();

        $event = new UserRegisterEvent($user, $mailer, $this->em);
        $this->eventDispatcher->addSubscriber(new RegisterEventSubscriber());
        $this->eventDispatcher->dispatch($event, UserRegisterEvent::NAME);

        return $this->json($user, Response::HTTP_CREATED, [], ['groups' => ContextGroup::CREATE_USER]);
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[OA\Post(
        path: '/vets/make_new',
        requestBody: new OA\RequestBody(
            description: 'Make new vet',
            required: true,
            content: new OA\JsonContent(
                ref: new Model(type: UserType::class)
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Returns new vet\'s data.',
                content: new Model(
                    type: User::class,
                    groups: [ContextGroup::CREATE_USER]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: 'Error'
            )
        ]
    )]
    #[Security(name: 'Bearer')]
    #[Route('/vets/make_new', methods: 'POST')]
    public function makeVet(Request $request, UserPasswordHasherInterface $passwordHasher, MailerInterface $mailer): Response
    {
        $vet = new User();

        $this->handleJSONForm($request, $vet, UserType::class);

        $plainPassword = UserService::makeVetTemporaryPassword($vet);
        $hashedPassword = $passwordHasher->hashPassword(
            $vet,
            $plainPassword
        );

        $vet->setPassword($hashedPassword);
        $vet->setAdditionalVetData();

        $this->em->persist($vet);
        $this->em->flush();

        $email = new TemplatedEmailService($mailer);
        $email->sendMailToNewVet($vet, $plainPassword);

        return $this->json($vet, Response::HTTP_CREATED, [], ['groups' => ContextGroup::SHOW_USER]);
    }

    #[OA\PUT(
        path: '/user/{id}',
        description: 'Edit user data here.',
        requestBody: new OA\RequestBody(
            description: 'User data from user form type.',
            required: true,
            content: new OA\MediaType(
                mediaType: OA\JsonContent::class,
                //comment to comment
                schema: new OA\Schema(
                    type: UserType::class
                )
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Returns updated user with new data.',
                content: new Model(type: User::class, groups: ['user_showAll'])
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'User not found',
//                content: new Model(type: User::class, groups: ['user_showAll'])
            ),
        ]
    )]
    #[Route('/user/{id}', methods: 'PUT')]
    public function edit(?User $user, Request $request, UserPasswordHasherInterface $passwordHasher, TokenStorageInterface $tokenStorage): Response
    {
        if (!$user) {
            return $this->json('User not found');
        }
        $this->handleJSONForm($request, $user, UserType::class);
        if ($user !== UserService::getCurrentUser($tokenStorage)) {
            return $this->json("Only user itself can edit his account.");
        }
        if ($user->getPlainPassword()) {
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $user->getPlainPassword()
            );
            $user->setPassword($hashedPassword);
        }
        $this->em->persist($user);
        $this->em->flush();

        return $this->json($user, Response::HTTP_CREATED, [], ['groups' => 'user_created']);
    }

    #[Security(name: 'Bearer')]
    #[Route('/me', methods: 'GET')]
    public function getCurrentUser(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json($user, Response::HTTP_OK, [], ['groups' => ContextGroup::ME]);
    }

    #[OA\Delete(
        path: '/user/{id}',
        description: 'Delete one user.',
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
                description: 'User with specified ID not found.'
            )
        ]
    )]
    #[Route('/user/{id}', methods: 'DELETE')]
    public function deleteUser(User $user): Response
    {

        $this->em->remove($user);
        $this->em->flush();

        return $this->json("", Response::HTTP_NO_CONTENT);
    }

    #[OA\Get(
        path: '/user',
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Get all examinations.',
                content: new Model(
                    type: User::class,
                    groups: [ContextGroup::SHOW_USER]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: 'Error occurred.',
            )
        ]
    )]
    #[Route('/user', methods: 'GET')]
    public function showAllUsers(UserRepository $repo): Response
    {
        $allUsers = $repo->findAll();//also paginate in free time

        return $this->json($allUsers, Response::HTTP_OK, [], ['groups' => ContextGroup::SHOW_USER]);
    }

    #[OA\Get(
        path: '/user/{id}',
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
                    type: User::class,
                    groups: [ContextGroup::SHOW_USER]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'User with specified ID not found.',
            )
        ]
    )]
    #[Route('/user/{id}', requirements: ['id' => Requirements::NUMERIC], methods: 'GET')]
    public function showOneUser(?User $user): Response
    {
        if (!$user) {
            return $this->json("User not found");
        }

        return $this->json($user, Response::HTTP_OK, [], ['groups' => ContextGroup::SHOW_USER]);
    }

    #[OA\Get(
        path: '/my_pets',
        parameters: [
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'number')
            ), new OA\Parameter(
                name: 'limit',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'number')
            )
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Show all pets of one user.',
                content: new Model(
                    type: User::class,
                    groups: [ContextGroup::SHOW_MY_PETS]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'User with specified ID not found.',
            )
        ]
    )]
    #[Security(name: 'Bearer')]
    #[Route('/my_pets', requirements: ['page' => Requirements::NUMERIC,'limit'=>Requirements::NUMERIC], methods: 'GET')]
    public function showMyPets(Request $request, #[CurrentUser] User $user, PaginatorInterface $paginator): Response
    {
        $myPets = $user->getPets()->toArray();

        $paginationService = new PaginationService($paginator, $request, $myPets);
        $paginatedResult = $paginationService->getPaginatedResult();

        return $this->json($paginatedResult, Response::HTTP_OK, [], ['groups' => ContextGroup::SHOW_MY_PETS]);
    }

    #[OA\Post(
        path: '/user/{id}/upload_image',
        description: 'Upload main image for user.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(
                            property: 'image',
                            type: 'file',
                            format: 'binary'
                        )
                    ]
                )
            )
        ),
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'User\'s ID.',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_CREATED,
                description: 'Returns path of the uploaded image.'
            ),
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Could return string if file has errors.',
            ),
        ]
    )]
    #[Route('/user/{id}/upload_image', requirements: ['id' => Requirements::NUMERIC], methods: 'POST')]
    public function uploadProfileImage(Request $request, User $user): Response
    {
        $uploadImage = new UploadImage($request, $user, $this->em);

        $uploadImage->upload();

        return $this->json($user, Response::HTTP_CREATED, [], ['groups' => ContextGroup::CREATE_USER]);
    }

    #[OA\Get(
        path: '/vet/{id}',
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
                description: 'One vet data.',
                content: new Model(
                    type: User::class,
                    groups: [ContextGroup::SHOW_VET]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'User with specified ID not found.',
            )
        ]
    )]
    #[Route('/vet/{id}')]
    public function showOneVet(User $vet, UserRepository $userRepo): Response
    {
        return $this->json($vet, Response::HTTP_OK, [], ['groups' => ContextGroup::SHOW_VET]);
    }

    #[OA\Get(
        path: '/vets/{id}/pets',
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
                description: 'One vet pets data.',
                content: new Model(
                    type: Pet::class,
                    groups: [ContextGroup::SHOW_PET]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Vet with specified ID not found.',
            )
        ]
    )]
    #[Security(name: 'Bearer')]
    #[Route('/vets/{id}/pets', requirements: ['id' => Requirements::NUMERIC], methods: 'GET')]
    public function getVetPetsData(User $vet): Response
    {
        $vetUsers = $vet->getClients();

        $pets = [];
        foreach ($vetUsers as $vetUser) {
            if ($vetUser->getPets() !== null) {
                $pets[] = $vetUser->getPets();
            }
        }
        return $this->json($pets, Response::HTTP_OK, [], ['groups' => ContextGroup::SHOW_PET]);
    }

    #[OA\Post(
        path: '/login_check',
        requestBody: new OA\RequestBody(
            description: 'Login',
            required: true,
            content: new OA\JsonContent(
                ref: new Model(type: LoginType::class)
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Returns logged in user.',
                content: new OA\JsonContent()
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: 'Wrong credentials.'
            )
        ]
    )]
    #[Route('/login_check', methods: 'POST')]
    public function login(UserRepository $userRepo): JsonResponse
    {
        $user = $userRepo->findAll();

        return $this->json($user, Response::HTTP_OK, ['application/json']);
    }

    #[Route('/vets/nearby', methods: 'GET')]
    public function nearbyVets(Request $request, UserRepository $userRepo): Response
    {
        //to be killed in near future
        $queryParams = (object)$request->query->all();
        $distance = $queryParams->distance;
        $latitude = $queryParams->latitude;
        $longitude = $queryParams->longitude;

        try {
            $nearbyVets = $userRepo->getNearbyVets($latitude, $longitude, $distance);
        } catch (Exception $e) {
            return $this->json($e, Response::HTTP_OK);
        }

        return $this->json($nearbyVets, Response::HTTP_OK, [], ['groups' => 'vet_nearby']);
    }

    #[OA\Get(
        parameters: [
            new OA\Parameter(
                name: 'from',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string')),
            new OA\Parameter(
                name: 'to',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Returns all vets that are free in time range.',
                content: new Model(type: User::class, groups: ['user_showAll'])
            )
        ]
    )]
    #[Route('/vets/free', methods: 'GET')]
    public function getFreeVetsInTimeRange(Request $request, TokenStorageInterface $tokenStorage, UserRepository $userRepo): Response
    {
        //todo check if this is working as it should be and try to make potential refactor
        $queryParams = (object)$request->query->all();

        $from = $queryParams->from;
        $to = $queryParams->to;

        // todo move to service
        $freeVets = $userRepo->getFreeVets($from, $to);
        $personalVet = UserService::getCurrentUser($tokenStorage)->getVet();
        if ($personalVet) {
            $freeVets[] = $this->addNotificationIfVetIsOccupied($personalVet, $freeVets);
        } else {
            $freeVets[] = ['notification' => 'You don\'t have personal vet.'];
        }
        return $this->json($freeVets, Response::HTTP_OK, [], ['groups' => 'user_showAll']);
    }

    private function addNotificationIfVetIsOccupied(User $personalVet, array $freeVets): array
    {
        if (!in_array($personalVet, $freeVets)) {
            return $freeVets[] = ['notification' => 'Your vet is occupied in this period of time, try to choose different time period.'];
        }
        return $freeVets[] = ['notification' => 'Your vet is free in chosen time range and you can reserve him.'];
    }

    #[OA\Get(
        path: '/vets',
        parameters: [
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'number')
            ), new OA\Parameter(
                name: 'limit',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'number')
            )
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Data for all vets.',
                content: new Model(
                    type: User::class,
                    groups: [ContextGroup::SHOW_VET]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: 'Error occurred.',
            )
        ]
    )]
    #[Route('/vets', methods: 'GET')]
    public function showAllVets(Request $request, UserRepository $userRepo, PaginatorInterface $paginator): Response
    {
        $allVets = $userRepo->allVets();

        $paginationService = new PaginationService($paginator, $request, $allVets);
        $paginatedResult = $paginationService->getPaginatedResult();

        return $this->json($paginatedResult, Response::HTTP_OK, [], ['groups' => ContextGroup::SHOW_VET]);
    }


    #[OA\Get(
        path: '/vet/{id}/health_record',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'number')
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'number'),
                example: 1
            ),
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'number'),
                example: 5
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Health records by one vet.',
                content: new Model(
                    type: HealthRecord::class,
                    groups: [ContextGroup::SHOW_HEALTH_RECORD]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: 'Vet with specified ID not found.',
            )
        ]
    )]
    #[Route('/vet/{id}/health_record', methods: 'GET')]
    public function getVetHealthRecords(?User $vet): Response
    {
        $vetHealthRecords = $vet->getHealthRecords();

        return $this->json($vetHealthRecords, Response::HTTP_OK, [], ['groups' => ContextGroup::SHOW_HEALTH_RECORD]);
    }

    #[OA\Post(
        path: '/log',
        description: 'Send empty POST request and we\'ll take you location',
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Returns nothing at all.',
            )
        ]
    )]
    #[Route('/log', methods: 'POST')]
    public function takeLocation(MobileDetectorInterface $detector): JsonResponse
    {
        $logHandler = new LogHandler();

        $log = $logHandler->getMyLoginLocation($detector);

        $this->em->persist($log);
        $this->em->flush();

        return $this->json("", Response::HTTP_OK);
    }
}
