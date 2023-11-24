<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class UserService
{
    public function handlePopularity(User $vet, int $examinationsCount):string
    {
        $countOfVetExaminations = count($vet->getHealthRecords());
        $percentage = 100 * $countOfVetExaminations / $examinationsCount;

        return number_format((float)$percentage, 2, '.', '') . '%';
    }

    public static function getCurrentUser(TokenStorageInterface $tokenStorage):?User
    {
        $token = $tokenStorage->getToken();
        if ($token instanceof TokenInterface)
        {
            /** @var User $user */
            $user = $token->getUser();
            return $user;
        }
        return null;
    }
}