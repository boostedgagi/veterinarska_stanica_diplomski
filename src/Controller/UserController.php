<?php

namespace App\Controller;

use App\Entity\Log;
use App\Entity\User;
use App\Entity\Token;
use App\Form\UserType;
use App\Repository\HealthRecordRepository;
use App\Repository\UserRepository;
use App\Repository\TokenEntityRepository;
use App\Service\LogHandler;
use App\Service\MobileDetectRepository;
use App\Service\TokenRepository;
use App\Service\uploadImage;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use MobileDetectBundle\DeviceDetector\MobileDetectorInterface;
use Nebkam\SymfonyTraits\FormTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\EmailRepository;

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
        $plainTextPassword = json_decode($request->getContent(), false);

        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $plainTextPassword->password
        );

        $this->handleJSONForm($request, $user, UserType::class);

        $user->setPassword($hashedPassword);
        $user->setAllowed(false);
        $user->setTypeOfUser(3);

        $email = new EmailRepository($mailer);
        $registrationRepo = new TokenRepository();

        $token = new Token($registrationRepo->makeNewToken());

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
    public function updateUser(Request $request, int $id, UserRepository $repo, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $repo->find($id);
        $plainTextPassword = json_decode($request->getContent(), false);

        $this->handleJSONForm($request, $user, UserType::class);

        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $plainTextPassword->password
        );

        $user->setPassword($hashedPassword);
        $this->em->persist($user);
        $this->em->flush();

        return $this->json($user, Response::HTTP_CREATED, [], ['groups' => 'user_created']);
    }

    #[Route('/users/{id}', methods: 'DELETE')]
    public function deleteUser(Request $request, int $id, UserRepository $repo): Response
    {
        $user = $repo->find($id);

        if ($user->getTypeOfUser() === 2) {
            $user->getUsers()->clear();

        }
        $this->em->remove($user);
        $this->em->flush();

        return $this->json("", Response::HTTP_NO_CONTENT);
    }

    #[Route('/users', methods: 'GET')]
    public function showAllUsers(Request $request, UserRepository $repo): Response
    {
        $allUsers = $repo->findAll();

        return $this->json($allUsers, Response::HTTP_OK, [], ['groups' => 'user_showAll']);
    }

    #[Route('/users/{id}', methods: 'GET')]
    public function showOneUser(Request $request, int $id, UserRepository $repo): Response
    {
        $user = $repo->find($id);

        return $this->json($user, Response::HTTP_OK, [], ['groups' => 'user_showAll']);
    }

    #[Route('/users/{id}/pets', methods: 'GET')]
    public function showOneUserPets(Request $request, int $id, UserRepository $repo): Response
    {
        $user = $repo->find($id);
        $pets = $user->getPets();
        return $this->json($pets, Response::HTTP_OK, [], ['groups' => 'pet_showByUser']);
    }

    #[Route('/password_verify/{id}', methods: 'POST')]
    public function passwordVerify(int $id, UserRepository $repo, Request $request): Response
    {
        $user = $repo->find($id);

        $data = json_decode($request->getContent(), false);

        $okay = password_verify($data->password, $user->getPassword());
        return $this->json($okay, Response::HTTP_OK, [], ['groups' => 'user_ok']);
    }

    #[Route('/user_upload_image/{id}', methods: 'POST')]
    public function uploadProfileImage(Request $request, UserRepository $repo, int $id): Response
    {
        $user = $repo->find($id);

        $uploadImage = new UploadImage($request, $user, $this->em);

        $uploadImage->upload();

        return $this->json($user, Response::HTTP_CREATED, [], ['groups' => 'user_created']);
    }

    #[Route('/vets/{id}/pets', methods: 'GET')]
    public function getVetPetsData(UserRepository $repo, int $id): Response
    {
        $vet = $repo->find($id);

        $vetUsers = $vet->getUsers();
        $pets = [];
        foreach ($vetUsers as $vetUser) {
            if ($vetUser->getPets() !== null) {
                $pets[] = $vetUser->getPets();
            }
        }
        return $this->json($pets, Response::HTTP_OK, [], ['groups' => 'pet_showAll']);
    }

    #[Route('/user/engage', methods: 'POST')]
    public function engageVet(Request $request, UserRepository $repo): Response
    {
        $data = json_decode($request->getContent(), false);

        $user = $repo->find($data->user_id);
        $vet = $repo->find($data->vet_id);
        if ($vet->getTypeOfUser() === 2 && $user->getTypeOfUser() === 3) {

            $user->setVet($vet);

            $this->em->persist($user);
            $this->em->flush();
            return $this->json($user, Response::HTTP_CREATED, [], ['groups' => 'user_created']);
        }
        return $this->json(['error' => 'he ain\'t vet'], Response::HTTP_OK);
    }

    #[Route('/users/{id}/change_type', methods: 'PATCH')]
    public function changeTypeOfUser(Request $request, int $id, UserRepository $repo): Response
    {
        $data = json_decode($request->getContent(), false);

        $user = $repo->find($id);
        $allowedTypes = [1, 2, 3];
        if ($data->typeOfUser) {

            $user->setTypeOfUser($data->typeOfUser);

            $this->em->persist($user);
            $this->em->flush();
            return $this->json($user, Response::HTTP_OK, [], ['groups' => 'user_created']);
        }
        return $this->json(['error' => 'type of user not valid'], Response::HTTP_OK);
    }

    #[Route('/login_check', methods: 'POST')]
    public function login(MobileDetectorInterface $detector, UserRepository $userRepo): JsonResponse
    {
        $user = $userRepo->findAll();

        return $this->json($user, Response::HTTP_OK);
    }

    #[Route('/vets/nearby', methods: 'GET')]
    public function nearbyVets(Request $request, UserRepository $userRepo): Response
    {
        $queryParams = (object)$request->query->all();

        $latitude = $queryParams->lat;
        $longitude = $queryParams->ltd;
        $distance = $queryParams->distance;

        try {
            $nearbyVets = $userRepo->getNearbyVets($latitude, $longitude, $distance);
        } catch (Exception $e) {
            return $this->json($e, Response::HTTP_OK);
        }

        return $this->json($nearbyVets, Response::HTTP_OK, [], ['groups' => 'vet_nearby']);
    }

    #[Route('/vets/free', methods: 'GET')]
    public function getFreeVetsInTimeRange(Request $request, UserRepository $userRepo, HealthRecordRepository $healthRecRepo): Response
    {
        $queryParams = (object)$request->query->all();

        $from = $queryParams->from;
        $to = $queryParams->to;

        $freeVets = $userRepo->getFreeVets($from, $to);

        return $this->json($freeVets, Response::HTTP_OK, [], ['groups' => 'user_showAll']);
    }

    #[Route('/public/vets', methods: 'GET')]
    public function showAllVets(Request $request, UserRepository $repo, HealthRecordRepository $healthRecordRepo): Response
    {
        $vets = $repo->findAll();

        $numberOfAllExaminations = $healthRecordRepo->examinationsCount();
        foreach ($vets as $vet) {

            $numberOfVetExaminations = count($vet->getHealthRecords());
            $percentage = 100 * $numberOfVetExaminations / $numberOfAllExaminations;

            $vet->setPopularity(
                number_format((float)$percentage, 2, '.', '') . '%');
        }

        return $this->json($vets, Response::HTTP_OK, [], ['groups' => 'user_showAll']);
    }

}
