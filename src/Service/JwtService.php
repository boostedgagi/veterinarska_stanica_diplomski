<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class JwtService
{
    private TokenStorageInterface $tokenStorage;

    public function __construct(TokenStorageInterface $storage)
    {
        $this->tokenStorage = $storage;
    }

    public function getCurrentUser():?User
    {
        $token = $this->tokenStorage->getToken();

        if ($token instanceof TokenInterface) {
            return $token->getUser();
        }
        return null;
    }
}