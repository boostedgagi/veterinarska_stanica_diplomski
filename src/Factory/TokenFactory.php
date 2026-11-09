<?php

namespace App\Factory;

use App\Entity\Token;
use App\Entity\User;
use App\Helper;
use Doctrine\ORM\EntityManagerInterface;

class TokenFactory
{
    public function __construct(public readonly EntityManagerInterface $em)
    {
    }

    public function save(User $user):Token
    {
        $token = $this->createToken()->setUser($user);
        $this->em->persist($token);
        $this->em->flush();
        return $token;
    }

    private function createToken(): Token
    {
        return new Token()
            ->setToken(bin2hex(random_bytes(32)))
            ->setExpires((string) (time() + Helper::ONE_HOUR_IN_SECONDS / 2));
    }
}