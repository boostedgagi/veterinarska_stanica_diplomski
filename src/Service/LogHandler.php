<?php

namespace App\Service;

use App\Entity\Log;
use MobileDetectBundle\DeviceDetector\MobileDetectorInterface;

class LogHandler
{
    public function getMyLoginLocation(MobileDetectorInterface $detector):Log
    {
        $deviceDetect = new MobileDetectRepository($detector);

        $export = (object)(unserialize(file_get_contents('http://www.geoplugin.net/php.gp')));

        return new Log(
            $deviceDetect->getDeviceInfo(),
            $export->geoplugin_countryName,
            $export->geoplugin_request
        );
    }
}