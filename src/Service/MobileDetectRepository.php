<?php

namespace App\Service;

use MobileDetectBundle\DeviceDetector\MobileDetectorInterface;

class MobileDetectRepository
{
    private MobileDetectorInterface $mobileDetector;

    /**
     * @param MobileDetectorInterface $mobileDetector
     */
    public function __construct(MobileDetectorInterface $mobileDetector)
    {
        $this->mobileDetector = $mobileDetector;
    }


    public function getDeviceInfo():string
    {

        $deviceType = ($this->mobileDetector->isMobile() ? ($this->mobileDetector->isTablet() ? 'Tablet' : 'Phone') : 'Computer');

        if ($deviceType === "Phone" OR $deviceType === "Tablet") {
//            echo "You are mobile! ";

            if ($this->mobileDetector->isiOS()) {
                return $deviceType.' iOS';
            }

            if ($this->mobileDetector->isAndroidOS()) {
                return $deviceType.' Android';
            }

        } else {
            return 'Computer';
        }
        return "Error";
    }

    public function getAccessingCountry(): ?string
    {
        return $this->mobileDetector->getUserAgent();
    }
}