<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class JwtService
{
    public static function getCurrentUser(TokenStorageInterface $tokenStorage):?User
    {
        $token = $tokenStorage->getToken();

        if ($token instanceof TokenInterface) {
            return $token->getUser();
        }
        return null;
    }
}