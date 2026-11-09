<?php

namespace App\Service;

use Detection\MobileDetect;
use App\Entity\Log;

class LogHandler
{
    public function getMyLoginLocation(MobileDetect $detector):Log
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