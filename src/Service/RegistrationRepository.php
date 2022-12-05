<?php

namespace App\Service;


use DateTime;

class RegistrationRepository
{
    public function handleToken():object
    {
        $hashedToken = md5(date().mt_rand(10,100));

        $rawExpires = new DateTime('+30 minutes');
        $expires = strtotime($rawExpires);

        return (object)[
            'tokenItself' => $hashedToken,
            'expires' => $expires
        ];
    }

}