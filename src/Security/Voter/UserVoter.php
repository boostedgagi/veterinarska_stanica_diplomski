<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Access to another user's account.
 *
 * - VIEW:   the user themselves, any vet, admins
 * - EDIT:   the user themselves, admins (also: a vet's own patient list / appointments)
 * - MANAGE: admins only (fields like allowed/verified)
 *
 * @extends Voter<string, User>
 */
final class UserVoter extends Voter
{
    public const string VIEW = 'USER_VIEW';
    public const string EDIT = 'USER_EDIT';
    public const string MANAGE = 'USER_MANAGE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::MANAGE], true) && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $current = $token->getUser();
        if (!$current instanceof User) {
            return false;
        }
        if (AccessHelper::isAdmin($current)) {
            return true;
        }

        return match ($attribute) {
            self::VIEW => AccessHelper::same($current, $subject) || AccessHelper::isVet($current),
            self::EDIT => AccessHelper::same($current, $subject),
            default => false,
        };
    }
}
