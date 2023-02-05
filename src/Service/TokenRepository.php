<?php

namespace App\Service;


use DateTime;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TokenRepository
{
    public function makeNewToken(): object
    {
        $hashedToken = md5(uniqid('', true) . mt_rand(10, 100));

        $expires = strtotime(date('Y-m-d h:i:s')) + (60 * 30);
        //duration of token is 30minutes

        return (object)[
            'tokenItself' => $hashedToken,
            'expires' => $expires
        ];
    }


}