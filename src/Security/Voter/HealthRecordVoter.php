<?php

namespace App\Security\Voter;

use App\Entity\HealthRecord;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Access to a health record (appointment).
 *
 * - VIEW, EDIT: the pet's owner, the record's vet, admins
 *               (EDIT is also checked on create and after the form is applied,
 *               so a record can't be booked on / moved to someone else's pet)
 * - DELETE:     the record's vet, admins
 *
 * @extends Voter<string, HealthRecord>
 */
final class HealthRecordVoter extends Voter
{
    public const string VIEW = 'HEALTH_RECORD_VIEW';
    public const string EDIT = 'HEALTH_RECORD_EDIT';
    public const string DELETE = 'HEALTH_RECORD_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true) && $subject instanceof HealthRecord;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $current = $token->getUser();
        if (!$current instanceof User) {
            return false;
        }
        if (AccessHelper::isAdmin($current) || AccessHelper::same($current, $subject->getVet())) {
            return true;
        }

        return $attribute !== self::DELETE && AccessHelper::same($current, $subject->getPet()?->getOwner());
    }
}
