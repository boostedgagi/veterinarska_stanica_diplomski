<?php

namespace App\Controller;

use App\Entity\Log;
use App\Service\MobileDetectRepository;
use Doctrine\ORM\Cache\Lock;
use Doctrine\ORM\EntityManagerInterface;
use MobileDetectBundle\DeviceDetector\MobileDetectorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LoginController extends AbstractController
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    #[Route('/login',methods:'GET')]
    public function login(MobileDetectorInterface $detector):Response
    {
        $deviceDetect = new MobileDetectRepository($detector);

        $ip = getenv('HTTP_X_FORWARDED_FOR');
        $export = (object)(unserialize(file_get_contents('http://www.geoplugin.net/php.gp?ip='.$ip)));

        $newLog = (object)[
            'device'=>$deviceDetect->getDeviceInfo(),
            'country'=>$export->geoplugin_countryName,
            'ip'=>$export->geoplugin_request
        ];

        $log = new Log($newLog->device,$newLog->country,$newLog->ip);
        $this->em->persist($log);
        $this->em->flush();

        return $this->json($log,Response::HTTP_OK);
    }
}
