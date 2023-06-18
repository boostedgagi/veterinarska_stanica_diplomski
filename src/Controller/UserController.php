<?php

namespace App\Controller;

use App\Entity\Log;
use App\Entity\User;
use App\Entity\Token;
use App\Model\Token as ModelToken;
use App\Form\UserType;
use App\Repository\HealthRecordRepository;
use App\Repository\UserRepository;
use App\Repository\TokenEntityRepository;
use App\Service\JwtService;
use App\Service\LogHandler;
use App\Service\MobileDetectRepository;
use App\Service\uploadImage;
use App\Service\UserService;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use MobileDetectBundle\DeviceDetector\MobileDetectorInterface;
use Nebkam\SymfonyTraits\FormTrait;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\EmailRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserController extends AbstractController
{
    use FormTrait;

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

//    #[Route('/roleadmin/{id}','POST')]
//    public function makeadmin(UserRepository $userRepo,int $id):Response
//    {
//        $user = $userRepo->find($id);
//        $user->setRoles(["ROLE_ADMIN"]);
//
//
//        $this->em->persist($user);
//        $this->em->flush();
//
//        return $this->json('proslo',Response::HTTP_OK);
//    }
//this method was used to update my user object with ROLE_ADMIN in order to use exact methods with only admin entry, Dragan

    #[Route('/users', methods: 'POST')]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, MailerInterface $mailer): Response
    {
        $user = new User();

        $this->handleJSONForm($request, $user, UserType::class);

        if ($plainPassword = $user->getPlainPassword()) {
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $plainPassword
            );
            $user->setPassword($hashedPassword);
        } else if (!$user->getPlainPassword()) {
            return $this->json('Invalid password');
        }
        $user->setRoles(["ROLE_USER"]);
        $user->setAllowed(false);
        $user->setTypeOfUser(3);

        $email = new EmailRepository($mailer);

        $token30minutes = (new ModelToken())->make30MinToken();
        $token = new Token($token30minutes);

        $this->em->persist($user);
        $this->em->flush();

        $this->em->persist($token);
        $this->em->flush();

        $email->sendWelcomeEmail($user, $token);

        return $this->json($user, Response::HTTP_CREATED, [], ['groups' => 'user_created']);
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

        $email = new EmailRepository($mailer);

        $this->em->persist($vet);
        $this->em->flush();

        $email->sendMailToNewVet($vet, $plainPassword);

        return $this->json($vet, Response::HTTP_CREATED, [], ['groups' => 'user_created']);
    }

    private function makeTemporaryPasswordForVet(User $user): string
    {
        return strtolower($user->getFirstName()) . strtolower($user->getPhone()) . strtolower($user->getLastName());
    }

    #[Route('/users/{id}', methods: 'PUT')]
    public function edit(Request $request, int $id, UserRepository $repo, UserPasswordHasherInterface $passwordHasher,JwtService $jwtService): Response
    {
        $user = $repo->find($id);

        $this->handleJSONForm($request, $user, UserType::class);
//        if($user!==JwtService::getCurrentUser())
//            {
//                return $this->json("Only user itself can delete his account.");
//            }
        if ($user->getPlainPassword())
            {
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
        if ($vet->getTypeOfUser() === User::TYPE_VET) {
            /** @var User $client */
            foreach ($vet->getClients() as $client) {
                $client->setVet($vet);
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
    public function showOneUser(int $id, UserRepository $repo, HealthRecordRepository $healthRecordRepo): Response
    {
        $user = $repo->find($id);
        $userService = new UserService();

        if ($user->getTypeOfUser() === 2) {

            $examinationsCount = $healthRecordRepo->examinationsCount();
            $popularity = $userService->handlePopularity($user, $examinationsCount);
            $user->setPopularity($popularity);
        }
        return $this->json($user, Response::HTTP_OK, [], ['groups' => 'user_showAll']);
    }

    #[Route('/users/{id}/pets', requirements: ['id' => Requirements::NUMERIC], methods: 'GET')]
    public function showOneUserPets(User $user, UserRepository $repo): Response
    {
        $pets = $user->getPets();

        return $this->json($pets, Response::HTTP_OK, [], ['groups' => 'pet_showByUser']);
    }

    #[Route('/user_upload_image/{id}', requirements: ['id' => Requirements::NUMERIC], methods: 'POST')]
    public function uploadProfileImage(Request $request, UserRepository $repo, int $id): Response
    {
        $user = $repo->find($id);

        $uploadImage = new UploadImage($request, $user, $this->em);

        $uploadImage->upload();

        return $this->json($user, Response::HTTP_CREATED, [], ['groups' => 'user_created']);
    }

    #[Route('/vets/{id}/pets', requirements: ['id' => Requirements::NUMERIC], methods: 'GET')]
    public function getVetPetsData(UserRepository $repo, int $id): Response
    {
        $vet = $repo->find($id);

        $vetUsers = $vet->getClients();
        $pets = [];
        foreach ($vetUsers as $vetUser) {
            if ($vetUser->getPets() !== null) {
                $pets[] = $vetUser->getPets();
            }
        }
        return $this->json($pets, Response::HTTP_OK, [], ['groups' => 'pet_showAll']);
    }

    #[Route('/engage_vet', methods: 'POST')]
    public function engageVet(Request $request, UserRepository $repo): Response
    {
        $data = json_decode($request->getContent(), false);

        //this can also be done by handling only vet because user_id can be found by jwtService's method
        $user = $repo->find($data->user_id);
        $vet = $repo->find($data->vet_id);

        if ($vet->isVet() && !$user->isVet()) {

            $user->setVet($vet);

            $this->em->persist($user);
            $this->em->flush();

            return $this->json($user, Response::HTTP_CREATED, [], ['groups' => 'user_created']);
        }
        return $this->json(['error' => 'someone is lying'], Response::HTTP_OK);
    }

    #[Route('/users/{id}/change_type', methods: 'PATCH')]
    public function changeTypeOfUser(Request $request, User $user): Response
    {
        $data = json_decode($request->getContent(), false);

        if ($data->typeOfUser && in_array($data->typeOfUser, [User::TYPE_ADMIN, User::TYPE_VET, User::TYPE_USER])) {

            $user->setTypeOfUser($data->typeOfUser);

            $this->em->persist($user);
            $this->em->flush();
            return $this->json($user, Response::HTTP_OK, [], ['groups' => 'user_created']);
        }
        return $this->json(['error' => 'type of user not valid'], Response::HTTP_OK);
    }

    #[Route('/get_id', methods: 'GET')]
    public function getId(Request $request,UserRepository $userRepo): JsonResponse
    {
        $email = $request->query->get('email');
        $id = $userRepo->getId($email);
        return $this->json($id, Response::HTTP_OK);
    }

    /**
     * @param UserRepository $userRepo
     * @return JsonResponse
     */
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

    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Returns all vets that are free in time range.',
        content: new Model(type: User::class,groups: ['user_showAll'])
    )]
    #[Route('/vets/free', methods: 'GET')]
    public function getFreeVetsInTimeRange(Request $request, TokenStorageInterface $tokenStorage, UserRepository $userRepo): Response
    {
        $queryParams = (object)$request->query->all();

        $from = $queryParams->from;
        $to = $queryParams->to;

        $freeVets = $userRepo->getFreeVets($from, $to);
        $personalVet = JwtService::getCurrentUser($tokenStorage)->getVet();
        if($personalVet)
            {
            $freeVets = $this->addNotificationIfVetIsOccupied($personalVet,$freeVets);
            }
        else
            {
            $freeVets[] = ['notification' => 'You don\'t have personal vet.'];
            }
        return $this->json($freeVets, Response::HTTP_OK, [], ['groups' => 'user_showAll']);
    }

    private function addNotificationIfVetIsOccupied(User $personalVet, array $freeVets):array
    {
        if (!in_array($personalVet, $freeVets))
            {
            return $freeVets[] = ['notification' => 'Your vet is occupied in this period of time, try to choose different time period.'];
            }
        return $freeVets[] = ['notification' => 'Your vet is free in chosen time range and you can reserve him.'];
    }

    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Returns all vets registered on this website.',
        content: new Model(type: User::class,groups: ['user_showAll'])
    )]
    #[Route('/get/vets', methods: 'GET')]
    public function showAll(Request $request, UserRepository $repo, HealthRecordRepository $healthRecordRepo): Response
    {
        $vets = $repo->findAll();

        $examinationsCount = $healthRecordRepo->examinationsCount();
        $userService = new UserService();
        foreach ($vets as $vet) {
            $vetPopularity = $userService->handlePopularity($vet, $examinationsCount);
            $vet->setPopularity($vetPopularity);
        }

        return $this->json($vets, Response::HTTP_OK, [], ['groups' => 'user_showAll']);
    }


}
