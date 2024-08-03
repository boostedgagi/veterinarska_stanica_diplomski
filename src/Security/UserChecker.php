<?php

namespace App\Security;

use App\Entity\User as AppUser;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{

    public function checkPreAuth(UserInterface $user):void
    {
        if($user instanceof AppUser){
            if(!$user->isAllowed()){
                throw new CustomUserMessageAccountStatusException('Access forbidden');
            }
        }
    }

    public function checkPostAuth(UserInterface $user):void
    {
        // TODO: Implement checkPostAuth() method.
    }
}