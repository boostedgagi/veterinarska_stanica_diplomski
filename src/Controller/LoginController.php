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
    public function login(MobileDetectorInterface $detector): JsonResponse
    {
        $logHandler = new LogHandler();

        $log = $logHandler->getMyLoginLocation($detector);

        $this->em->persist($log);
        $this->em->flush();

        return $this->json(['status'=>'Location taken.'],Response::HTTP_OK);
    }
}
