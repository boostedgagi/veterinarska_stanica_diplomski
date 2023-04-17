<?php

namespace App\Service;


use App\Helper;
use DateTime;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TokenRepository
{
    public function makeNewToken(): object
    {
        $hashedToken = md5(uniqid('', true) . mt_rand(10, 100));

        //duration of token is 30minutes
        $expires = strtotime(date('Y-m-d h:i:s')) + (Helper::ONE_HOUR_IN_SECONDS / 2);

        return (object)[
            'tokenItself' => $hashedToken,
            'expires' => $expires
        ];
    }


}