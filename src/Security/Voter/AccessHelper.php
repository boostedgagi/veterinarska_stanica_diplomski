<?php

namespace App\Security\Voter;

use App\Entity\User;

/**
 * Shared checks for the ownership voters.
 */
final class AccessHelper
{
    /**
     * Admin status is stored in two ways in this project: ROLE_ADMIN in roles
     * (used by #[IsGranted('ROLE_ADMIN')]) or typeOfUser = TYPE_ADMIN. Either counts.
     */
    public static function isAdmin(User $user): bool
    {
        return $user->isAdmin() || in_array('ROLE_ADMIN', $user->getRoles(), true);
    }

    public static function isVet(User $user): bool
    {
        return $user->getTypeOfUser() === User::TYPE_VET;
    }

    public static function same(?User $a, ?User $b): bool
    {
        if ($a === null || $b === null) {
            return false;
        }

        return $a === $b || ($a->getId() !== null && $a->getId() === $b->getId());
    }
}
