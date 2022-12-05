<?php

namespace App\Service;


use DateTime;

class RegistrationRepository
{
    public function handleToken():object
    {
        $hashedToken = md5(uniqid('', true).mt_rand(10,100));

        $expires = strtotime(date('Y-m-d h:i:s'))+(60*30);

        return (object)[
            'tokenItself' => $hashedToken,
            'expires' => $expires
        ];
    }

}