<?php

namespace App\Service;

use MobileDetectBundle\DeviceDetector\MobileDetectorInterface;

class MobileDetectRepository
{
    public function __construct(
        public readonly MobileDetectorInterface $mobileDetector
    )
    {
    }

    public function getDeviceInfo():string
    {

        $deviceType = ($this->mobileDetector->isMobile() ? ($this->mobileDetector->isTablet() ? 'Tablet' : 'Phone') : 'Computer');

        if ($deviceType === "Phone" || $deviceType === "Tablet") {

            if ($this->mobileDetector->isiOS()) {
                return $deviceType.' iOS';
            }

            if ($this->mobileDetector->isAndroidOS()) {
                return $deviceType.' Android';
            }
        }
        else {
            return 'Computer';
        }
        return "Error";
    }

    public function getAccessingCountry(): ?string
    {
        return $this->mobileDetector->getUserAgent();
    }
}