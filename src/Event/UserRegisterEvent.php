<?php

namespace App\Event;

use App\Entity\User;

class UserRegisterEvent
{
    public const NAME = 'user.register';

    public function __construct(
        public readonly User $user
    )
    {
    }
}