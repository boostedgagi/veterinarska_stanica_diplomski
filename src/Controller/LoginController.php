<?php

namespace App\Controller;

use App\Service\MobileDetectRepository;
use MobileDetectBundle\DeviceDetector\MobileDetectorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LoginController extends AbstractController
{
    #[Route('/login',methods:'GET')]
    public function login(MobileDetectorInterface $detector):Response
    {
        $deviceDetect = new MobileDetectRepository($detector);

        $ip = getenv('HTTP_X_FORWARDED_FOR');
//        dd($_SERVER);
        $export = (object)(unserialize(file_get_contents('http://www.geoplugin.net/php.gp?ip='.$ip)));

        $newLog = (object)[
            'device'=>$deviceDetect->getDeviceInfo(),
            'country'=>$export->geoplugin_countryName,
            'ip'=>$export->geoplugin_request
        ];

        return $this->json($newLog,Response::HTTP_OK);
    }
}
