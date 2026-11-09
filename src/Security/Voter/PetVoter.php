<?php

namespace App\Security\Voter;

use App\Entity\Pet;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Access to a pet.
 *
 * - VIEW: the owner, admins, and the pet's vets: the owner's assigned vet or
 *         a vet who has a health record (appointment) for this pet
 * - EDIT: the owner, admins (also used for creating: the owner must be you)
 *
 * @extends Voter<string, Pet>
 */
final class PetVoter extends Voter
{
    public const string VIEW = 'PET_VIEW';
    public const string EDIT = 'PET_EDIT';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT], true) && $subject instanceof Pet;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $current = $token->getUser();
        if (!$current instanceof User) {
            return false;
        }
        if (AccessHelper::isAdmin($current) || AccessHelper::same($current, $subject->getOwner())) {
            return true;
        }

        return $attribute === self::VIEW && self::isVetOf($current, $subject);
    }

    public static function isVetOf(User $vet, Pet $pet): bool
    {
        if (!AccessHelper::isVet($vet)) {
            return false;
        }
        if (AccessHelper::same($vet, $pet->getOwner()?->getVet())) {
            return true;
        }
        foreach ($pet->getHealthRecords() as $healthRecord) {
            if (AccessHelper::same($vet, $healthRecord->getVet())) {
                return true;
            }
        }

        return false;
    }
}
