<?php

namespace App\Security\Voter;

use App\Entity\User;

/**
 * Shared checks for the ownership voters.
 */
final class AccessHelper
{
    public static function isAdmin(User $user): bool
    {
        return $user->isAdmin();
    }

    public static function isVet(User $user): bool
    {
        return $user->isVet();
    }

    public static function same(?User $a, ?User $b): bool
    {
        if ($a === null || $b === null) {
            return false;
        }

        return $a === $b || ($a->getId() !== null && $a->getId() === $b->getId());
    }
}
