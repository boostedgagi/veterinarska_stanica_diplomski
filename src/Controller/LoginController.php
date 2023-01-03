<?php

namespace App\Controller;

use App\Entity\Log;
use App\Repository\UserRepository;
use App\Service\LogHandler;
use App\Service\MobileDetectRepository;
use Doctrine\ORM\Cache\Lock;
use Doctrine\ORM\EntityManagerInterface;
use MobileDetectBundle\DeviceDetector\MobileDetectorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LoginController extends AbstractController
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    #[Route('/take_location', methods: 'POST')]
    public function login(MobileDetectorInterface $detector, UserRepository $userRepo): JsonResponse
    {
        $logHandler = new LogHandler();

        $log = $logHandler->getMyLocation($detector);

        $this->em->persist($log);
        $this->em->flush();
        //maybe I should retrieve coordinates from
        //method from above and patch user object with them in case he will need nearest vet, who knows.
        return $this->json(['status'=>'Location taken.'],Response::HTTP_OK);
//        $user = $userRepo->findAll();
//
//        return $this->json($user, Response::HTTP_OK);
    }
}
