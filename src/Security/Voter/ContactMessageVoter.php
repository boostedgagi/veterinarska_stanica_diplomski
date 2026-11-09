<?php

namespace App\Security\Voter;

use App\Entity\ContactMessage;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Chat messages.
 *
 * - VIEW:      sender, receiver, admins (a chat is visible to the people in it)
 * - MARK_SEEN: the receiver, admins
 *
 * @extends Voter<string, ContactMessage>
 */
final class ContactMessageVoter extends Voter
{
    public const string VIEW = 'CONTACT_MESSAGE_VIEW';
    public const string MARK_SEEN = 'CONTACT_MESSAGE_MARK_SEEN';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::MARK_SEEN], true) && $subject instanceof ContactMessage;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $current = $token->getUser();
        if (!$current instanceof User) {
            return false;
        }
        if (AccessHelper::isAdmin($current) || AccessHelper::same($current, $subject->getReceiver())) {
            return true;
        }

        return $attribute === self::VIEW && AccessHelper::same($current, $subject->getSender());
    }
}
