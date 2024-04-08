<?php

namespace App\Factory;

use App\Entity\Token;
use App\Helper;
use Doctrine\ORM\EntityManagerInterface;

class TokenFactory
{
    public function __construct(public readonly EntityManagerInterface $em)
    {
    }

    public function save():Token
    {
        $token = $this->createToken();

        $this->em->persist($token);
        $this->em->flush();

        return $token;
    }

    private function createToken(): Token
    {
        return (new Token())
            ->setToken(md5(uniqid('', true) . mt_rand(10, 100)))
            ->setExpires(strtotime(date('Y-m-d h:i:s')) + (Helper::ONE_HOUR_IN_SECONDS / 2));
    }
}