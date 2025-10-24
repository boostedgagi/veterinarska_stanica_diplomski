<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class UserService
{
    public static function calculateVetPopularity(User $vet, int $allExaminationsCount): string
    {
        $vetExaminationsCount = count($vet->getHealthRecords());
        if($allExaminationsCount==0){
            return "0";
        }
        $percentage = 100 * $vetExaminationsCount / $allExaminationsCount;

        return number_format((float)$percentage, 2, '.', '');
    }

    public static function makeVetTemporaryPassword($user): string
    {
        return strtolower($user->getFirstName()) . strtolower($user->getPhone()) . strtolower($user->getLastName());

    }

    public static function getCurrentUser(TokenStorageInterface $tokenStorage): ?User
    {
        $token = $tokenStorage->getToken();
        if ($token instanceof TokenInterface) {
            /** @var User $user */
            $user = $token->getUser();
            return $user;
        }
        return null;
    }
}