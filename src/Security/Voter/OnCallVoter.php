<?php

namespace App\Security\Voter;

use App\Entity\OnCall;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * An on-call session can only be started / changed by its own vet (or an admin).
 *
 * @extends Voter<string, OnCall>
 */
final class OnCallVoter extends Voter
{
    public const string EDIT = 'ON_CALL_EDIT';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::EDIT && $subject instanceof OnCall;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $current = $token->getUser();

        return $current instanceof User
            && (AccessHelper::isAdmin($current) || AccessHelper::same($current, $subject->getVet()));
    }
}
