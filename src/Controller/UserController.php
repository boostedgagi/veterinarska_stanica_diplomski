<?php

namespace App\Controller;

use App\ContextGroup;
use App\Entity\User;
use App\Entity\Token;
use App\Event\UserRegisterEvent;
use App\EventSubscriber\RegisterEventSubscriber;
use App\Model\Token as ModelToken;
use App\Form\UserType;
use App\Repository\HealthRecordRepository;
use App\Repository\UserRepository;
use App\Service\LogHandler;
use App\Service\uploadImage;
use App\Service\UserService;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use MobileDetectBundle\DeviceDetector\MobileDetectorInterface;
use Nebkam\SymfonyTraits\FormTrait;
use Nelmio\ApiDocBundle\Annotation\Model;
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
use App\Service\TemplatedEmail;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserController extends AbstractController
{
    use FormTrait;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EventDispatcherInterface $eventDispatcher
    )
    {
    }

    #[OA\Post(
        path:'/user',
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
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, MailerInterface $mailer): Response
    {
        $user = new User();

        $this->handleJSONForm($request, $user, UserType::class);
        if(!$user->getPlainPassword()){
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

        $event = new UserRegisterEvent($user,$mailer,$this->em);
        $this->eventDispatcher->addSubscriber(new RegisterEventSubscriber());
        $this->eventDispatcher->dispatch($event, UserRegisterEvent::NAME);

        return $this->json($user, Response::HTTP_CREATED, [], ['groups' => ContextGroup::CREATE_USER]);
    }

    #[Route('/make_vet', methods: 'POST')]
    public function makeVet(Request $request, UserPasswordHasherInterface $passwordHasher, MailerInterface $mailer): Response
    {
        $vet = new User();

        $this->handleJSONForm($request, $vet, UserType::class);

        $plainPassword = $this->makeTemporaryPasswordForVet($vet);
        $hashedPassword = $passwordHasher->hashPassword(
            $vet,
            $plainPassword
        );

        $vet->setPassword($hashedPassword);
        $vet->setRoles(["ROLE_VET"]);
        $vet->setAllowed(true);
        $vet->setTypeOfUser(2);

        $email = new TemplatedEmail($mailer);

        $this->em->persist($vet);
        $this->em->flush();

        $email->sendMailToNewVet($vet, $plainPassword);

        return $this->json($vet, Response::HTTP_CREATED, [], ['groups' => 'user_created']);
    }

    private function makeTemporaryPasswordForVet(User $user): string
    {
        return strtolower($user->getFirstName()) . strtolower($user->getPhone()) . strtolower($user->getLastName());
    }

    #[OA\PUT(
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
        )
        ,
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
    #[Route('/users/{id}', methods: 'PUT')]
    public function edit(?User $user, Request $request, UserPasswordHasherInterface $passwordHasher, TokenStorageInterface $tokenStorage): Response
    {
        if (!$user) {
            return $this->json('User not found');
        }
        $this->handleJSONForm($request, $user, UserType::class);
        if ($user !== UserService::getCurrentUser($tokenStorage)) {
            return $this->json("Only user itself can delete his account.");
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

    #[Route('/users/{id}', methods: 'DELETE')]
    public function deleteUser(User $vet,): Response
    {
        // todo set on delete null on User->get users
        if ($vet->getTypeOfUser() === User::TYPE_VET) {
            /** @var User $client */
            foreach ($vet->getClients() as $client) {
                $client->setVet(null);
            }
        }
        $this->em->remove($vet);
        $this->em->flush();

        return $this->json("", Response::HTTP_NO_CONTENT);
    }

    #[Route('/users', methods: 'GET')]
    public function showAllUsers(Request $request, UserRepository $repo): Response
    {
        $allUsers = $repo->findAll();

        return $this->json($allUsers, Response::HTTP_OK, [], ['groups' => 'user_showAll']);
    }

    #[Route('/users/{id}', requirements: ['id' => Requirements::NUMERIC], methods: 'GET')]
    public function showOneUser(?User $user, HealthRecordRepository $healthRecordRepo): Response
    {
        if (!$user) {
            return $this->json("User not found");
        }

        return $this->json($user, Response::HTTP_OK, [], ['groups' => 'user_showAll']);
    }

    #[Route('/users/{id}/pets', requirements: ['id' => Requirements::NUMERIC], methods: 'GET')]
    public function showOneUserPets(User $user, UserRepository $repo): Response
    {
        $pets = $user->getPets();

        return $this->json($pets, Response::HTTP_OK, [], ['groups' => ContextGroup::SHOW_USER_PETS]);
    }

    #[Route('/user_upload_image/{id}', requirements: ['id' => Requirements::NUMERIC], methods: 'POST')]
    public function uploadProfileImage(Request $request, UserRepository $repo, User $user): Response
    {
        $uploadImage = new UploadImage($request, $user, $this->em);

        $uploadImage->upload();

        return $this->json($user, Response::HTTP_CREATED, [], ['groups' => ContextGroup::CREATE_USER]);
    }

    #[Route('/vets/{id}/pets', requirements: ['id' => Requirements::NUMERIC], methods: 'GET')]
    public function getVetPetsData(UserRepository $repo,User $vet): Response
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

    #[Route('/login_check', methods: 'POST')]
    public function login(UserRepository $userRepo): JsonResponse
    {
        $user = $userRepo->findAll();

        return $this->json($user, Response::HTTP_OK, ['application/json']);
    }

    #[Route('/vets/nearby', methods: 'GET')]
    public function nearbyVets(Request $request, UserRepository $userRepo): Response
    {
        //need to think about enabling this route to public access
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
        $queryParams = (object)$request->query->all();

        $from = $queryParams->from;
        $to = $queryParams->to;

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

    #[Route('/get/vets', methods: 'GET')]
    public function showAll(UserRepository $userRepo): Response
    {
        $vets = $userRepo->getAllVets();

        return $this->json($vets, Response::HTTP_OK, [], ['groups' => ContextGroup::SHOW_USER]);
    }

    #[Route('/vet/{id}/health_records', methods: 'GET')]
    public function getVetHealthRecords(?User $vet): Response
    {
        $vetHealthRecords = $vet->getHealthRecords();

        return $this->json($vetHealthRecords, Response::HTTP_OK, [], ['groups' => ContextGroup::SHOW_HEALTH_RECORD]);
    }

    #[Route('/take_location', methods: 'POST')]
    public function takeLocation(MobileDetectorInterface $detector): JsonResponse
    {
        $logHandler = new LogHandler();

        $log = $logHandler->getMyLoginLocation($detector);

        $this->em->persist($log);
        $this->em->flush();

        return $this->json("",Response::HTTP_OK);
    }
}
